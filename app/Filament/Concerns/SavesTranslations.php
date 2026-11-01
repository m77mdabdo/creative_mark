<?php

namespace App\Filament\Concerns;

use App\Enums\ReviewStatus;
use App\Translation\ContentTranslator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * For Create/Edit pages of models using App\Models\Concerns\HasTranslations.
 * Fills `translations.{locale}.*` from the translation rows and writes them back in the same transaction.
 * A locale edited by someone who cannot approve translations returns to "review" (D-05).
 * After saving, empty languages are machine translated in the background (after the response is sent).
 */
trait SavesTranslations
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['translations'] = $this->getRecord()->loadMissing('translations')->translations
            ->mapWithKeys(fn (Model $t) => [$t->locale => [...$t->attributesToArray(), 'review_status' => $t->review_status->value]])
            ->all();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $record = static::getModel()::create($this->withoutTranslations($data));
            $this->saveTranslations($record, $data['translations'] ?? []);

            return $record;
        });
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record->update($this->withoutTranslations($data));
            $this->saveTranslations($record, $data['translations'] ?? []);

            return $record;
        });
    }

    /** @param array<string, mixed> $data */
    private function withoutTranslations(array $data): array
    {
        unset($data['translations']);

        return $data;
    }

    /** @param array<string, array<string, mixed>> $translations */
    private function saveTranslations(Model $record, array $translations): void
    {
        $canApprove = (bool) auth()->user()?->can('translations.approve');

        foreach ($translations as $locale => $fields) {
            $status = $fields['review_status'] ?? null;
            unset($fields['review_status'], $fields['id'], $fields['locale'], $fields['created_at'], $fields['updated_at'], $fields['reviewed_at']);
            $fields = array_intersect_key($fields, array_flip($record->translations()->getRelated()->getFillable()));

            $existing = $record->translations()->firstWhere('locale', $locale);
            $hasText = collect($fields)->filter(fn ($v) => filled($v))->isNotEmpty();
            if (! $existing && ! $hasText) {
                continue;
            }

            $translation = $existing ?? $record->translations()->make(['locale' => $locale]);
            $translation->fill($fields);

            if ($canApprove && $status) {
                $translation->review_status = ReviewStatus::from($status);
                $translation->reviewed_at = $status === ReviewStatus::Approved->value ? now() : $translation->reviewed_at;
            } elseif ($translation->isDirty() || ! $translation->exists) {
                $translation->review_status = $hasText ? ReviewStatus::Review : ReviewStatus::Missing;
            }

            $translation->save();
        }

        $this->translateEmptyLanguagesLater($record);
    }

    private function translateEmptyLanguagesLater(Model $record): void
    {
        if (! config('translation.auto_on_save')) {
            return;
        }

        dispatch(function () use ($record) {
            try {
                app(ContentTranslator::class)->translateRecord($record->fresh());
            } catch (Throwable $e) {
                Log::warning('Automatic translation failed', ['record' => $record::class.'#'.$record->getKey(), 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }
}
