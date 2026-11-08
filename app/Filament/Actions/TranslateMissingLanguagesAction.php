<?php

namespace App\Filament\Actions;

use App\Translation\ContentTranslator;
use App\Translation\TranslationUnavailable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/** Header action: machine translate the empty languages of this record (results marked "machine"). */
final class TranslateMissingLanguagesAction
{
    public static function make(): Action
    {
        return Action::make('translateMissing')
            ->label(__('Translate missing languages'))
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->visible(fn () => (bool) auth()->user()?->can('translations.edit'))
            ->requiresConfirmation()
            ->modalDescription(__('Empty languages are filled by machine translation and marked for review. Text a person wrote is never replaced.'))
            ->action(function (Model $record, $livewire) {
                try {
                    // On a live record the new languages go into the draft (see SavesTranslations).
                    $count = method_exists($livewire, 'translateMissingLanguages')
                        ? $livewire->translateMissingLanguages()
                        : app(ContentTranslator::class)->translateRecord($record);
                } catch (TranslationUnavailable $e) {
                    Notification::make()->danger()->title(__('Translation service unavailable'))->body($e->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title(__(':count languages translated', ['count' => $count]))->send();
                method_exists($livewire, 'translateMissingLanguages') || $livewire->refreshFormData(['translations']);
            });
    }
}
