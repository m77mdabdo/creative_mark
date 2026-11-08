<?php

namespace App\Content;

use App\Enums\ReviewStatus;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Support\Locales;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Makes the figures or client reviews match a list edited in the page editor: creates, updates, reorders and deletes
 * records, with one translation row per language. An empty language is removed, so the site falls back to English.
 */
final class CatalogueSync
{
    public const KINDS = ['stats', 'testimonials'];

    /** @param list<array<string, mixed>> $items */
    public function sync(string $kind, array $items): void
    {
        match ($kind) {
            'stats' => $this->run(Stat::class, $items, fn (array $i, int $order, ?Model $existing) => [
                'key' => $existing?->key ?? 'stat-'.Str::lower(Str::random(6)), 'value' => $i['value'], 'icon' => $i['icon'] ?? null,
                'group' => $i['group'] ?? 'secondary', 'verified' => (bool) ($i['verified'] ?? false), 'source' => $i['source'] ?? null, 'sort_order' => $order,
            ], fn (array $i) => ['label' => $i['label'] ?? []]),
            'testimonials' => $this->run(Testimonial::class, $items, fn (array $i, int $order, ?Model $existing) => [
                'status' => ($i['published'] ?? true) ? 'published' : 'draft', 'verified' => (bool) ($i['verified'] ?? false), 'sort_order' => $order,
                'consent_recorded_at' => ($i['consent'] ?? false) ? ($existing?->consent_recorded_at ?? now()) : null,
            ], fn (array $i) => ['quote' => $i['quote'] ?? [], 'author' => $i['author'] ?? [], 'context' => $i['context'] ?? []]),
            default => throw new InvalidArgumentException("Unknown catalogue {$kind}."),
        };
    }

    /** The current records in the editor's shape. @return list<array<string, mixed>> */
    public function items(string $kind): array
    {
        return match ($kind) {
            'stats' => Stat::query()->with('translations')->orderBy('sort_order')->get()->map(fn (Stat $s) => [
                'id' => $s->id, 'value' => $s->value, 'icon' => $s->icon, 'group' => $s->group, 'verified' => $s->verified, 'source' => $s->source,
                'label' => $s->translations->pluck('label', 'locale')->all(),
            ])->all(),
            'testimonials' => Testimonial::query()->with('translations')->orderBy('sort_order')->get()->map(fn (Testimonial $t) => [
                'id' => $t->id, 'published' => $t->status === 'published', 'consent' => $t->consent_recorded_at !== null, 'verified' => $t->verified,
                'quote' => $t->translations->pluck('quote', 'locale')->all(), 'author' => $t->translations->pluck('author', 'locale')->all(),
                'context' => $t->translations->pluck('context', 'locale')->all(),
            ])->all(),
            default => throw new InvalidArgumentException("Unknown catalogue {$kind}."),
        };
    }

    /** @param class-string<Model> $model */
    private function run(string $model, array $items, Closure $attributes, Closure $texts): void
    {
        DB::transaction(function () use ($model, $items, $attributes, $texts) {
            $keep = [];
            foreach (array_values($items) as $order => $item) {
                $existing = filled($item['id'] ?? null) ? $model::query()->find($item['id']) : null;
                $record = $existing ?? new $model;
                $record->forceFill($attributes($item, $order + 1, $existing))->save();
                $keep[] = $record->id;
                $this->writeTexts($record, $texts($item));
            }
            $model::query()->whereNotIn('id', $keep)->get()->each->delete();
        });
    }

    /** @param array<string, array<string, ?string>> $fields field => locale => text */
    private function writeTexts(Model $record, array $fields): void
    {
        $byLocale = [];
        foreach ($fields as $field => $map) {
            foreach ((array) $map as $locale => $value) {
                if (isset(Locales::ALL[$locale])) {
                    $byLocale[$locale][$field] = filled($value) ? trim(strip_tags((string) $value)) : null;
                }
            }
        }
        foreach ($byLocale as $locale => $values) {
            if (! array_filter($values) && $locale !== Locales::FALLBACK) {
                $record->translations()->where('locale', $locale)->delete();

                continue;
            }
            $translation = $record->translations()->firstOrNew(['locale' => $locale]);
            $translation->fill($values);
            if ($translation->isDirty()) {
                $translation->review_status = $locale === Locales::FALLBACK ? ReviewStatus::Approved : ReviewStatus::Review;
            }
            $translation->save();
        }
    }
}
