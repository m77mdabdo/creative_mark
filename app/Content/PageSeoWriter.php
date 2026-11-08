<?php

namespace App\Content;

use App\Enums\ReviewStatus;
use App\Models\Page;
use App\Support\Locales;

/** A page's name and Google texts per language. A changed language goes back to review unless the editor may approve it. */
final class PageSeoWriter
{
    /** @param array<string, array<string, ?string>> $seo locale => {title, seo_title, seo_description} */
    public function write(Page $page, array $seo): void
    {
        $canApprove = (bool) auth()->user()?->can('translations.approve');
        $page->loadMissing('translations');
        foreach (Locales::codes() as $locale) {
            $values = array_map(fn ($v) => filled($v) ? trim((string) $v) : null, array_intersect_key($seo[$locale] ?? [], array_flip(['title', 'seo_title', 'seo_description'])));
            $translation = $page->translations->firstWhere('locale', $locale);
            if (! $translation && ! array_filter($values)) {
                continue;
            }
            $translation ??= $page->translations()->make(['locale' => $locale, 'slug' => $page->key === 'home' ? null : $page->key]);
            $translation->fill($values);
            if ($translation->isDirty() && $locale !== Locales::FALLBACK && ! $canApprove) {
                $translation->review_status = ReviewStatus::Review;
            }
            $translation->review_status ??= ReviewStatus::Review;
            if (blank($translation->title)) {
                $translation->title = $page->t('title', Locales::FALLBACK) ?? $page->key;
            }
            $translation->save();
        }
    }

    /** @return array<string, array<string, ?string>> */
    public function read(Page $page): array
    {
        return $page->loadMissing('translations')->translations
            ->mapWithKeys(fn ($t) => [$t->locale => ['title' => $t->title, 'seo_title' => $t->seo_title, 'seo_description' => $t->seo_description]])->all();
    }
}
