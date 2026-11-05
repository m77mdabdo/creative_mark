<?php

namespace App\Mail;

use App\Consultation\MailStrings;
use App\Filament\Resources\NewsArticles\NewsArticleResource;
use App\Models\NewsArticle;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a publisher that a scheduled story has just gone live. */
class ScheduledStoryPublished extends Mailable
{
    public function __construct(public NewsArticle $story, public string $staffLocale) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: MailStrings::get($this->staffLocale, 'story_subject', ['title' => str_replace(["\r", "\n"], ' ', (string) $this->story->t('title', $this->staffLocale))]));
    }

    public function content(): Content
    {
        $locale = $this->staffLocale;

        return new Content(view: 'mail.story-published', with: [
            'locale' => $locale,
            'title' => MailStrings::get($locale, 'story_intro'),
            'kicker' => MailStrings::get($locale, 'story_kicker'),
            'preheader' => (string) $this->story->t('title', $locale),
            'headline' => (string) $this->story->t('title', $locale),
            'excerpt' => (string) $this->story->t('excerpt', $locale),
            'when' => $this->story->publish_at->timezone(config('consultation.timezone'))->locale($locale)->translatedFormat('l j F Y · H:i').' ('.MailStrings::get($locale, 'riyadh_time').')',
            'siteUrl' => url("/{$locale}/news/{$this->story->slug}"),
            'dashboardUrl' => NewsArticleResource::getUrl('edit', ['record' => $this->story]),
        ]);
    }
}
