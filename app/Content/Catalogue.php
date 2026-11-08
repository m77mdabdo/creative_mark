<?php

namespace App\Content;

use App\Models\Country;
use App\Models\Faq;
use App\Models\Region;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Support\Locales;

/**
 * The shared catalogue that many pages show, as the site sends it: live records only, in display order, in one
 * language. RULE-01 (verified figures only) and RULE-02 (names only with consent) are applied here.
 */
final class Catalogue
{
    /**
     * Every published, reviewed question with its category, for the FAQ page.
     *
     * @return array{categories: list<array{key: string, label: string}>, items: list<array<string, mixed>>}
     */
    public function faqLibrary(string $locale): array
    {
        $labels = Setting::group('catalogue')['faq_categories'] ?? [];
        $items = Faq::query()->live()->orderBy('sort_order')->withLocale($locale)->get()
            ->map(fn (Faq $m) => ['id' => $m->id, 'category' => $m->category, 'question' => $m->t('question', $locale), 'answer' => $m->t('answer', $locale)])->all();
        $used = array_unique(array_column($items, 'category'));

        return [
            'categories' => array_values(array_map(fn ($key) => ['key' => $key, 'label' => $labels[$key][$locale] ?? $labels[$key][Locales::FALLBACK] ?? $key],
                array_values(array_filter(array_keys($labels), fn ($k) => in_array($k, $used, true))))),
            'items' => $items,
        ];
    }

    /**
     * Services, regions, figures, countries, questions, reviews and the catalogue wording in one language.
     *
     * @return array<string, mixed>
     */
    public function forLocale(string $locale, int $faqLimit): array
    {
        $services = Service::query()->live()->orderBy('sort_order')->withLocale($locale)->get();
        $slugs = $services->pluck('slug', 'id');
        $industries = Setting::group('catalogue')['industries'] ?? [];

        return [
            'services' => $services->map(fn (Service $m) => [
                'slug' => $m->slug, 'icon' => $m->icon, 'media' => $m->media, 'order' => $m->sort_order, 'group' => $m->group,
                'title' => $m->t('title', $locale), 'tagline' => $m->t('tagline', $locale),
                // RULE-01: a summary quoting an unverified figure is replaced by the tagline
                'summary' => $m->needs_review ? $m->t('tagline', $locale) : $m->t('summary', $locale), 'cta' => $m->t('cta', $locale),
            ])->all(),
            'regions' => Region::query()->live()->orderBy('sort_order')->withLocale($locale)->with('services:id')->get()
                ->map(fn (Region $m) => [
                    'slug' => $m->slug, 'media' => $m->media,
                    'name' => $m->t('name', $locale), 'capital' => $m->t('capital', $locale),
                    'bestFor' => $m->t('best_for', $locale), 'description' => $m->t('description', $locale),
                    'opportunities' => $m->translation($locale)?->opportunities ?? [],
                    'industries' => $m->industries ?? [],
                    'services' => $m->services->map(fn ($s) => $slugs[$s->id] ?? null)->filter()->values()->all(),
                ])->all(),
            'industries' => array_map(fn ($l) => $l[$locale] ?? $l[Locales::FALLBACK], $industries),
            'serviceGroups' => array_map(fn ($l) => $l[$locale] ?? $l[Locales::FALLBACK], Setting::group('catalogue')['service_groups'] ?? []),
            // The investor journey (presentation p39) used by the process section and the formation page.
            'journey' => array_map(fn (array $step) => [
                'title' => $step['title'][$locale] ?? $step['title'][Locales::FALLBACK],
                'text' => $step['text'][$locale] ?? $step['text'][Locales::FALLBACK],
            ], Setting::group('catalogue')['journey'] ?? []),
            // RULE-01: only verified figures are sent
            'stats' => Stat::query()->live()->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn (Stat $m) => ['key' => $m->key, 'value' => $m->value, 'icon' => $m->icon, 'group' => $m->group, 'label' => $m->t('label', $locale)])->all(),
            'countries' => Country::query()->live()->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn (Country $m) => ['code' => $m->code, 'name' => $m->t('name', $locale)])->all(),
            'faqs' => Faq::query()->live()->where('show_on_home', true)
                ->orderBy('sort_order')->limit($faqLimit)->withLocale($locale)->get()
                ->map(fn (Faq $m) => ['id' => $m->id, 'question' => $m->t('question', $locale), 'answer' => $m->t('answer', $locale)])->all(),
            // RULE-02: names only with recorded consent; otherwise the neutral "Client review" label
            'testimonials' => Testimonial::query()->live()->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn (Testimonial $m) => [
                    'id' => $m->id, 'quote' => $m->t('quote', $locale), 'context' => $m->t('context', $locale),
                    'author' => $m->consent_recorded_at ? $m->t('author', $locale) : null,
                    'verified' => $m->verified && $m->consent_recorded_at !== null,
                ])->all(),
        ];
    }
}
