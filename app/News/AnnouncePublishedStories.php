<?php

namespace App\News;

use App\Filament\Resources\NewsArticles\NewsArticleResource;
use App\Mail\ScheduledStoryPublished;
use App\Models\NewsArticle;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells the people who can publish that a scheduled story has gone live: a bell in the dashboard and an email, each in
 * the person's dashboard language. Stories published straight away are not announced (the publisher just did it).
 * Each story is claimed with one atomic update, so overlapping runs can never announce it twice.
 */
final class AnnouncePublishedStories
{
    public function __invoke(): int
    {
        $announced = 0;
        $due = NewsArticle::query()->where('status', 'published')->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())->whereNull('live_notified_at')->with('translations')->get();

        foreach ($due as $story) {
            $claimed = NewsArticle::query()->whereKey($story->id)->whereNull('live_notified_at')->update(['live_notified_at' => now()]);
            if ($claimed) {
                $this->announce($story);
                $announced++;
            }
        }

        return $announced;
    }

    private function announce(NewsArticle $story): void
    {
        $team = User::query()->where('is_active', true)->get()->filter(fn (User $u) => $u->can('content.publish'));
        foreach ($team as $user) {
            $locale = in_array($user->admin_locale, ['ar', 'en'], true) ? $user->admin_locale : 'ar';
            $headline = $story->t('title', $locale);

            try {
                Notification::make()
                    ->title(__('Scheduled story published', [], $locale))
                    ->body($headline)
                    ->icon('heroicon-o-newspaper')->iconColor('success')
                    ->actions([
                        Action::make('view')->label(__('View on the website', [], $locale))->url(url("/{$locale}/news/{$story->slug}"), shouldOpenInNewTab: true),
                        Action::make('edit')->label(__('Edit', [], $locale))->url(NewsArticleResource::getUrl('edit', ['record' => $story])),
                    ])
                    ->sendToDatabase($user);
            } catch (Throwable $e) {
                Log::warning('Story bell notification failed', ['story' => $story->id, 'user' => $user->id, 'error' => $e->getMessage()]);
            }

            try {
                Mail::to($user->email, $user->name)->send(new ScheduledStoryPublished($story, $locale));
            } catch (Throwable $e) {
                // A mail problem never undoes the publishing, and the bell above has already gone out.
                Log::warning('Story email failed', ['story' => $story->id, 'user' => $user->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
