<?php

namespace App\Content;

use App\Enums\ReviewStatus;
use App\Translation\ContentTranslator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes a dashboard form's data to a record that uses HasTranslations: its own columns, `translations.{locale}.*`
 * (one row per language) and many-to-many lists named in `relations`. Used for direct saves and when a draft is published.
 * A language changed by someone who cannot approve translations returns to "review" (D-05).
 */
final class RecordWriter
{
    /** @param class-string<Model> $model */
    public function create(string $model, array $data, array $relations = []): Model
    {
        return DB::transaction(function () use ($model, $data, $relations) {
            $record = $model::create($this->ownColumns($data, $relations));
            $this->write($record, $data, $relations);

            return $record;
        });
    }

    public function update(Model $record, array $data, array $relations = []): Model
    {
        return DB::transaction(function () use ($record, $data, $relations) {
            $record->update($this->ownColumns($data, $relations));
            $this->write($record, $data, $relations);

            return $record;
        });
    }

    /** Fills empty languages by machine translation after the response is sent; a failure is only logged. */
    public function translateEmptyLanguagesLater(Model $record): void
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

    private function write(Model $record, array $data, array $relations): void
    {
        foreach ($relations as $relation) {
            if (array_key_exists($relation, $data) && $record->{$relation}() instanceof BelongsToMany) {
                $record->{$relation}()->sync(array_map('intval', (array) $data[$relation]));
            }
        }
        $this->writeTranslations($record, $data['translations'] ?? []);
    }

    /** @param array<string, mixed> $data */
    private function ownColumns(array $data, array $relations): array
    {
        return array_diff_key($data, array_flip(['translations', ...$relations]));
    }

    /** @param array<string, array<string, mixed>> $translations */
    private function writeTranslations(Model $record, array $translations): void
    {
        $canApprove = (bool) auth()->user()?->can('translations.approve');
        $fillable = array_flip($record->translations()->getRelated()->getFillable());

        foreach ($translations as $locale => $fields) {
            $status = $fields['review_status'] ?? null;
            $fields = array_intersect_key($fields, $fillable);
            unset($fields['locale'], $fields['review_status'], $fields['reviewed_at']);

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
    }
}
