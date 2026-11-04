<?php

namespace App\Content;

use App\Models\Page;
use App\Models\Region;
use App\Support\Locales;
use App\Translation\UiStrings;
use Illuminate\Support\Str;

/**
 * Builds the editable page of a Saudi region (page key "region-{slug}") from the region record and the interface
 * wording, in every language it has: hero, where the opportunity lies with the map and leading sectors, recommended
 * solutions, more regions and a call to action. Same layout as before; edited in Pages › Regions.
 */
final class RegionPageBuilder
{
    public static function key(string $slug): string
    {
        return "region-{$slug}";
    }

    /** Creates and publishes the page if the region has none yet. */
    public static function ensure(Region $region): Page
    {
        $page = Page::query()->where('key', self::key($region->slug))->first();
        if ($page) {
            return $page;
        }
        $region->loadMissing('translations');
        $page = Page::create(['key' => self::key($region->slug), 'draft_sections' => self::sections($region)]);
        foreach ($region->translations as $t) {
            $page->translations()->create([
                'locale' => $t->locale, 'slug' => 'regions/'.$region->slug, 'title' => $t->name,
                'seo_title' => Str::limit("{$t->name} | {$t->best_for} | Creative Mark", 150, ''),
                'seo_description' => Str::limit((string) $t->description, 300),
                'review_status' => $t->review_status,
            ]);
        }
        (new PublishPage)($page, null, 'Created from the region record');

        return $page;
    }

    /** @return list<array<string, mixed>> */
    public static function sections(Region $region): array
    {
        $region->loadMissing('translations');
        $field = fn (string $f) => array_filter($region->translations->mapWithKeys(fn ($t) => [$t->locale => $t->{$f}])->all(), fn ($v) => filled($v));
        $ui = fn (string $key) => array_filter(collect(Locales::codes())->mapWithKeys(fn ($l) => [$l => UiStrings::forLocale($l)[$key] ?? null])->all(), 'filled');
        $consultation = "/consultation?region={$region->slug}";
        $style = fn (string $theme, int $overlay = 55, string $padding = 'lg', string $align = 'start', ?string $bg = null) => array_filter(
            ['theme' => $theme, 'overlay' => $overlay, 'padding' => $padding, 'align' => $align, 'bgMedia' => $bg], fn ($v) => $v !== null);
        $motion = fn (string $preset) => ['preset' => $preset, 'intensity' => 100, 'delay' => 0];
        $capital = array_filter($region->translations->mapWithKeys(fn ($t) => [$t->locale => $t->capital ? (UiStrings::forLocale($t->locale)['capital'] ?? 'Capital').': '.$t->capital : null])->all(), 'filled');

        // Opportunities as cards: one card per chip, matched by position across languages.
        $lists = $region->translations->mapWithKeys(fn ($t) => [$t->locale => $t->opportunities ?? []]);
        $cards = [];
        foreach ($lists[Locales::FALLBACK] ?? [] as $i => $unused) {
            $cards[] = ['id' => 'opportunity-'.($i + 1), 'title' => array_filter($lists->map(fn ($items) => $items[$i] ?? null)->all(), 'filled'), 'verified' => true];
        }

        $sections = [
            ['id' => 'region-hero', 'type' => 'hero', 'label' => 'Hero', 'eyebrow' => $capital, 'title' => $field('name'), 'subtitle' => $field('best_for'), 'media' => $region->media,
                'buttons' => [['label' => $ui('discussRegion'), 'href' => $consultation, 'style' => 'gold'], ['label' => $ui('allRegions'), 'href' => '/regions', 'style' => 'outline']],
                'style' => $style('dark', 55, 'md'), 'motion' => $motion('zoom-in')],
            ['id' => 'region-overview', 'type' => 'regionOverview', 'label' => 'Opportunity', 'eyebrow' => $ui('whereOpportunity'), 'title' => $field('best_for'), 'body' => $field('description'),
                'subtitle' => $ui('opportunities'), 'cards' => $cards, 'style' => $style('dark'), 'motion' => $motion('fade-up')],
            ['id' => 'region-services', 'type' => 'relatedServices', 'label' => 'Recommended solutions', 'eyebrow' => $ui('recommended'), 'title' => $ui('regionServices'), 'limit' => 6,
                'style' => $style('charcoal'), 'motion' => $motion('stagger-cards')],
            ['id' => 'region-more', 'type' => 'moreRegions', 'label' => 'More regions', 'title' => $ui('otherRegions'), 'limit' => 3,
                'style' => $style('dark'), 'motion' => $motion('stagger-cards')],
            ['id' => 'region-cta', 'type' => 'cta', 'label' => 'Call to action', 'title' => $ui('discussRegion'), 'body' => $ui('ctaBody'),
                'buttons' => [['label' => $ui('bookConsultation'), 'href' => $consultation, 'style' => 'gold']],
                'style' => $style('dark', 74, 'lg', 'center', 'about-kingdom-centre'), 'motion' => $motion('reveal-mask')],
        ];

        return array_map(fn ($s) => array_filter($s, fn ($v) => $v !== null && $v !== []) + ['buttons' => [], 'cards' => [], 'slides' => []], $sections);
    }

    /** Copies the published page's name, focus, text and opportunities back to the region (cards, map, booking form). */
    public static function syncToRegion(Page $page): void
    {
        $region = Region::query()->where('slug', substr($page->key, 7))->with('translations')->first();
        $sections = collect($page->publishedRevision?->sections ?? []);
        $hero = $sections->firstWhere('type', 'hero');
        $overview = $sections->firstWhere('type', 'regionOverview');
        if (! $region || ! $hero) {
            return;
        }
        foreach (Locales::codes() as $locale) {
            $values = array_filter([
                'name' => $hero['title'][$locale] ?? null,
                'best_for' => $hero['subtitle'][$locale] ?? null,
                'description' => $overview['body'][$locale] ?? null,
            ], 'filled');
            if ($overview) {
                $values['opportunities'] = array_values(array_filter(array_map(fn ($c) => $c['title'][$locale] ?? null, $overview['cards'] ?? []), 'filled'));
            }
            $translation = $region->translations->firstWhere('locale', $locale);
            if ($translation && $values) {
                $translation->fill($values)->save();
            }
        }
    }
}
