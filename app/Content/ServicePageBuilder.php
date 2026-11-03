<?php

namespace App\Content;

use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Locales;
use App\Translation\UiStrings;
use Illuminate\Support\Str;

/**
 * Builds the editable page of a business solution (page key "service-{slug}") from the service record and the
 * interface wording, in every language the service has: hero, overview with what is included, the investor
 * journey (company formation only), questions, related solutions and a call to action. The result looks
 * exactly like the original fixed layout and is then edited in Pages › Business solutions.
 */
final class ServicePageBuilder
{
    public static function key(string $slug): string
    {
        return "service-{$slug}";
    }

    /** Creates and publishes the page if the service has none yet. */
    public static function ensure(Service $service): Page
    {
        $page = Page::query()->where('key', self::key($service->slug))->first();
        if ($page) {
            return $page;
        }
        $page = Page::create(['key' => self::key($service->slug), 'draft_sections' => self::sections($service)]);
        foreach ($service->translations as $t) {
            $page->translations()->create([
                'locale' => $t->locale, 'slug' => 'services/'.$service->slug, 'title' => $t->title,
                'seo_title' => "{$t->title} | Creative Mark", 'seo_description' => Str::limit(trim(($t->tagline ? $t->tagline.'. ' : '').$t->summary), 300),
                'review_status' => $t->review_status,
            ]);
        }
        (new PublishPage)($page, null, 'Created from the service record');

        return $page;
    }

    /** @return list<array<string, mixed>> */
    public static function sections(Service $service): array
    {
        $service->loadMissing('translations');
        $field = fn (string $f) => array_filter($service->translations->mapWithKeys(fn ($t) => [$t->locale => $t->{$f}])->all(), fn ($v) => filled($v));
        $ui = fn (string $key) => array_filter(collect(Locales::codes())->mapWithKeys(fn ($l) => [$l => UiStrings::forLocale($l)[$key] ?? null])->all(), 'filled');
        $groups = Setting::group('catalogue')['service_groups'] ?? [];
        $consultation = "/consultation?service={$service->slug}";
        $style = fn (string $theme, int $overlay = 55, string $padding = 'lg', string $align = 'start', ?string $bg = null) => array_filter(
            ['theme' => $theme, 'overlay' => $overlay, 'padding' => $padding, 'align' => $align, 'bgMedia' => $bg], fn ($v) => $v !== null);
        $motion = fn (string $preset) => ['preset' => $preset, 'intensity' => 100, 'delay' => 0];

        // "What's included" items as cards: one card per scope line, matched by position across languages.
        $scopes = $service->translations->mapWithKeys(fn ($t) => [$t->locale => $t->scope ?? []]);
        $cards = [];
        foreach ($scopes[Locales::FALLBACK] ?? [] as $i => $line) {
            $title = array_filter($scopes->map(fn ($items) => is_array($items[$i] ?? null) ? ($items[$i]['en'] ?? null) : ($items[$i] ?? null))->all(), 'filled');
            $cards[] = ['id' => 'scope-'.($i + 1), 'title' => $title, 'verified' => true];
        }

        $sections = [
            ['id' => 'service-hero', 'type' => 'hero', 'label' => 'Hero', 'eyebrow' => $groups[$service->group] ?? null,
                'title' => $field('title'), 'subtitle' => $field('tagline'), 'media' => $service->media,
                'buttons' => [['label' => $field('cta'), 'href' => $consultation, 'style' => 'gold'], ['label' => $ui('allServices'), 'href' => '/services', 'style' => 'outline']],
                'cards' => [], 'slides' => [], 'style' => $style('dark', 58, 'md'), 'motion' => $motion('zoom-in')],
            ['id' => 'service-overview', 'type' => 'serviceOverview', 'label' => 'Overview', 'eyebrow' => $ui('overview'),
                'title' => $field('tagline'), 'body' => $field('overview'), 'note' => $field('note'), 'subtitle' => $ui('whatsIncluded'),
                'buttons' => [], 'cards' => $cards, 'slides' => [], 'style' => $style('dark'), 'motion' => $motion('fade-up')],
        ];
        if ($service->slug === 'company-formation') {
            $sections[] = ['id' => 'service-journey', 'type' => 'processSteps', 'label' => 'Journey', 'eyebrow' => $ui('howWeWork'), 'title' => $ui('fromIdea'),
                'buttons' => [], 'cards' => [], 'slides' => [], 'style' => $style('charcoal'), 'motion' => $motion('stagger-cards')];
        }
        $sections[] = ['id' => 'service-faq', 'type' => 'faq', 'label' => 'Questions', 'eyebrow' => $ui('questions'), 'title' => $ui('serviceQuestions'),
            'limit' => 10, 'buttons' => [], 'cards' => [], 'slides' => [], 'style' => $style('charcoal'), 'motion' => $motion('fade-up')];
        $sections[] = ['id' => 'service-related', 'type' => 'relatedServices', 'label' => 'Related solutions', 'eyebrow' => $ui('allServices'), 'title' => $ui('relatedServices'),
            'limit' => 3, 'buttons' => [], 'cards' => [], 'slides' => [], 'style' => $style('dark'), 'motion' => $motion('stagger-cards')];
        $sections[] = ['id' => 'service-cta', 'type' => 'cta', 'label' => 'Call to action', 'title' => $ui('ctaTitle'), 'body' => $ui('ctaBody'),
            'buttons' => [['label' => $ui('bookConsultation'), 'href' => $consultation, 'style' => 'gold']],
            'cards' => [], 'slides' => [], 'style' => $style('dark', 74, 'lg', 'center', 'about-kingdom-centre'), 'motion' => $motion('reveal-mask')];

        return array_map(fn ($s) => array_filter($s, fn ($v) => $v !== null && $v !== []) + ['buttons' => [], 'cards' => [], 'slides' => []], $sections);
    }
}
