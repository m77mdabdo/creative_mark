<?php

namespace App\Drafts;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single place where unpublished changes are stored, published, discarded and previewed.
 * Changes to content that visitors can see wait here until someone presses Publish.
 */
final class Drafts
{
    /** @param class-string<DraftTarget> $target */
    public function put(string $key, string $target, array $payload, ?string $label = null, ?string $editUrl = null): Draft
    {
        if (! is_subclass_of($target, DraftTarget::class)) {
            throw new InvalidArgumentException("{$target} is not a draft target.");
        }

        return Draft::query()->updateOrCreate(['key' => $key], ['target' => $target, 'payload' => $payload, 'label' => $label, 'edit_url' => $editUrl, 'updated_by' => auth()->id()]);
    }

    public function find(string $key): ?Draft
    {
        return Draft::query()->where('key', $key)->first();
    }

    public function has(string $key): bool
    {
        return Draft::query()->where('key', $key)->exists();
    }

    public function discard(string $key): void
    {
        Draft::query()->where('key', $key)->delete();
    }

    /** Makes the change live and forgets the draft. Returns false when there was nothing to publish. */
    public function publish(string $key): bool
    {
        return DB::transaction(function () use ($key) {
            $draft = Draft::query()->where('key', $key)->lockForUpdate()->first();
            if (! $draft) {
                return false;
            }
            $this->apply($draft);
            $draft->delete();

            return true;
        });
    }

    /** Writes every draft to the live tables. Only for previews, inside a transaction that is rolled back. */
    public function applyAll(): void
    {
        Draft::query()->orderBy('updated_at')->get()->each(fn (Draft $d) => $this->apply($d));
    }

    /**
     * Runs $callback with this draft applied, then undoes it: used to show a screen's saved draft with the same code
     * that reads the live values.
     */
    public function peek(string $key, Closure $callback): mixed
    {
        $draft = $this->find($key);
        if (! $draft) {
            return $callback();
        }
        DB::beginTransaction();
        try {
            $this->apply($draft);

            return $callback();
        } finally {
            DB::rollBack();
        }
    }

    public static function recordKey(Model $record): string
    {
        return 'record:'.class_basename($record).':'.$record->getKey();
    }

    private function apply(Draft $draft): void
    {
        $target = app($draft->target);
        if (! $target instanceof DraftTarget) {
            throw new InvalidArgumentException("{$draft->target} is not a draft target.");
        }
        $target->apply($draft->payload);
    }
}
