<?php

namespace App\Filament\Support;

use Closure;
use Filament\Actions\Action;

/**
 * The same three buttons on every screen that edits something visitors see: Preview, Publish and Discard.
 * Saving is the screen's own primary button ("Save draft").
 */
final class DraftActions
{
    /** Saves, then opens the site in preview mode at $path (no language prefix). $save returns false when it could not save. */
    public static function preview(Closure $save, Closure $path): Action
    {
        return Action::make('preview')->label(__('Preview'))->icon('heroicon-o-eye')->color('gray')
            ->action(function ($livewire) use ($save, $path) {
                if ($save() === false) {
                    return;
                }
                $url = route('preview.start', ['path' => $path(), 'locale' => app()->getLocale()]);
                $livewire->js('window.open('.json_encode($url).', "_blank")');
            });
    }

    /** $publish saves and makes the changes live. */
    public static function publish(Closure $publish, Closure $visible): Action
    {
        return Action::make('publish')->label(__('Publish'))->icon('heroicon-o-rocket-launch')->color('primary')
            ->visible($visible)
            ->requiresConfirmation()->modalHeading(__('Publish these changes?'))->modalDescription(__('Visitors will see these changes straight away.'))
            ->action($publish);
    }

    public static function discard(Closure $discard, Closure $visible): Action
    {
        return Action::make('discard')->label(__('Discard unpublished changes'))->icon('heroicon-o-arrow-uturn-left')->color('danger')->link()
            ->visible($visible)
            ->requiresConfirmation()->modalDescription(__('The screen goes back to what visitors see now.'))
            ->action($discard);
    }
}
