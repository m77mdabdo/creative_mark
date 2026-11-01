<?php

namespace App\Models\Concerns;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One *_translations row per locale (ADR-0003). Reads fall back to English, never to another locale.
 *
 * @mixin Model
 */
trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel());
    }

    abstract protected function translationModel(): string;

    /** Eager-load only the current locale and the English fallback. */
    public function scopeWithLocale(Builder $query, string $locale): Builder
    {
        return $query->with(['translations' => fn ($q) => $q->whereIn('locale', array_unique([$locale, Locales::FALLBACK]))]);
    }

    public function translation(string $locale): ?Model
    {
        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', Locales::FALLBACK);
    }

    public function t(string $field, string $locale): ?string
    {
        $own = $this->translations->firstWhere('locale', $locale)?->{$field};
        if (filled($own)) {
            return $own;
        }

        return $this->translations->firstWhere('locale', Locales::FALLBACK)?->{$field};
    }
}
