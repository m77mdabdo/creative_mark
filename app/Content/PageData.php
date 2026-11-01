<?php

namespace App\Content;

use App\Enums\ReviewStatus;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Region;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Support\Locales;

/**
 * Builds the props of a CMS page (home, about…) from its published revision and the catalogue.
 * Everything filtered by a content rule is removed here, so it never reaches the browser.
 */
final class PageData
{
    /** @return array<string, mixed> */
    public function forPage(string $key, string $locale): array
    {
        $page = Page::query()->where('key', $key)->withLocale($locale)->with('publishedRevision')->firstOrFail();
        abort_unless($page->publishedRevision, 404);

        $faqLimit = 6;
        $sections = [];
        foreach ($page->publishedRevision->sections as $s) {
            if (! empty($s['hidden'])) {
                continue; // FR-021: hidden sections are absent from the HTML
            }
            if ($s['type'] === 'faq') {
                $faqLimit = (int) ($s['limit'] ?? 6);
            }
            $sections[] = $this->localizeSection($s, $locale);
        }

        $catalogue = $this->catalogue($locale, $faqLimit);
        $sections = array_values(array_filter($sections, fn ($s) => match ($s['type']) {
            'trackRecord' => $catalogue['stats'] !== [],
            'testimonials' => $catalogue['testimonials'] !== [],
            'faq' => $catalogue['faqs'] !== [],
            default => true,
        }));

        $translation = $page->translations->firstWhere('locale', $locale);

        return [
            'pageKey' => $page->key,
            'title' => $page->t('title', $locale),
            'seo' => [
                'title' => $page->t('seo_title', $locale),
                'description' => $page->t('seo_description', $locale),
            ],
            'reviewStatus' => ($translation?->review_status ?? ReviewStatus::Missing)->value,
            'sections' => $sections,
            ...$catalogue,
            'media' => $this->media($sections, $catalogue, $locale),
        ];
    }

    /**
     * A business solution's detail page: the service itself, its linked FAQs (never ones needing review)
     * and up to three related services.
     *
     * @return array<string, mixed>
     */
    public function forService(string $slug, string $locale): array
    {
        $service = Service::query()->where('slug', $slug)->where('status', 'published')->withLocale($locale)
            ->with(['faqs' => fn ($q) => $q->where('status', 'published')->where('needs_review', false)->orderBy('sort_order')->withLocale($locale)])
            ->firstOrFail();
        $catalogue = $this->catalogue($locale, 0);
        $bySlug = collect($catalogue['services'])->keyBy('slug');
        $related = collect($service->related ?? [])->map(fn ($s) => $bySlug[$s] ?? null)->filter()->take(3)->values()->all();
        $translation = $service->translation($locale);
        $title = $service->t('title', $locale);

        $props = [
            'pageKey' => 'service',
            'title' => $title,
            'seo' => ['title' => "{$title} | Creative Mark", 'description' => $service->t('tagline', $locale).'. '.$service->t('summary', $locale)],
            'reviewStatus' => ($translation?->review_status ?? ReviewStatus::Missing)->value,
            'service' => [
                'slug' => $service->slug, 'icon' => $service->icon, 'media' => $service->media, 'group' => $service->group,
                'title' => $title, 'tagline' => $service->t('tagline', $locale), 'overview' => $service->t('overview', $locale),
                'scope' => $translation?->scope ?: ($service->translation(Locales::FALLBACK)?->scope ?? []),
                'note' => $service->t('note', $locale), 'cta' => $service->t('cta', $locale),
            ],
            'related' => $related,
            'serviceFaqs' => $service->faqs->map(fn (Faq $f) => ['id' => $f->id, 'question' => $f->t('question', $locale), 'answer' => $f->t('answer', $locale)])->all(),
            'journey' => $service->slug === 'company-formation' ? $catalogue['journey'] : [],
            'serviceGroups' => $catalogue['serviceGroups'],
            'stats' => $catalogue['stats'],
        ];
        $props['media'] = $this->mediaFor([$service->media, ...array_column($related, 'media'), 'about-kingdom-centre'], $locale);

        return $props;
    }

    /** @param array<string, mixed> $s */
    private function localizeSection(array $s, string $locale): array
    {
        $pick = fn (?array $map) => $map === null ? null : (filled($map[$locale] ?? null) ? $map[$locale] : $map[Locales::FALLBACK]);

        foreach (['eyebrow', 'title', 'highlight', 'subtitle', 'body'] as $k) {
            if (isset($s[$k])) {
                $s[$k] = $pick($s[$k]);
            }
        }
        $s['buttons'] = array_map(fn ($b) => [...$b, 'label' => $pick($b['label'])], $s['buttons'] ?? []);
        // RULE-01: a card quoting an unverified figure is not published
        $s['cards'] = array_values(array_map(
            fn ($c) => [...$c, 'title' => $pick($c['title'] ?? null), 'text' => $pick($c['text'] ?? null)],
            array_filter($s['cards'] ?? [], fn ($c) => ($c['verified'] ?? true) === true),
        ));
        $s['slides'] = array_map(fn ($slide) => [
            ...$slide,
            ...array_map($pick, array_intersect_key($slide, array_flip(['eyebrow', 'title', 'highlight', 'subtitle']))),
        ], $s['slides'] ?? []);
        unset($s['label']);

        return $s;
    }

    /** @return array<string, mixed> */
    private function catalogue(string $locale, int $faqLimit): array
    {
        $services = Service::query()->where('status', 'published')->orderBy('sort_order')->withLocale($locale)->get();
        $slugs = $services->pluck('slug', 'id');
        $industries = Setting::group('catalogue')['industries'] ?? [];

        return [
            'services' => $services->map(fn (Service $m) => [
                'slug' => $m->slug, 'icon' => $m->icon, 'media' => $m->media, 'order' => $m->sort_order, 'group' => $m->group,
                'title' => $m->t('title', $locale), 'tagline' => $m->t('tagline', $locale),
                // RULE-01: a summary quoting an unverified figure is replaced by the tagline
                'summary' => $m->needs_review ? $m->t('tagline', $locale) : $m->t('summary', $locale), 'cta' => $m->t('cta', $locale),
            ])->all(),
            'regions' => Region::query()->where('status', 'published')->orderBy('sort_order')->withLocale($locale)->with('services:id')->get()
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
            'stats' => Stat::query()->where('verified', true)->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn (Stat $m) => ['key' => $m->key, 'value' => $m->value, 'icon' => $m->icon, 'label' => $m->t('label', $locale)])->all(),
            'countries' => Country::query()->where('status', 'published')->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn (Country $m) => ['code' => $m->code, 'name' => $m->t('name', $locale)])->all(),
            'faqs' => Faq::query()->where('status', 'published')->where('show_on_home', true)->where('needs_review', false)
                ->orderBy('sort_order')->limit($faqLimit)->withLocale($locale)->get()
                ->map(fn (Faq $m) => ['id' => $m->id, 'question' => $m->t('question', $locale), 'answer' => $m->t('answer', $locale)])->all(),
            // RULE-02: names only with recorded consent; otherwise the neutral "Client review" label
            'testimonials' => Testimonial::query()->where('status', 'published')->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn (Testimonial $m) => [
                    'id' => $m->id, 'quote' => $m->t('quote', $locale), 'context' => $m->t('context', $locale),
                    'author' => $m->consent_recorded_at ? $m->t('author', $locale) : null,
                    'verified' => $m->verified && $m->consent_recorded_at !== null,
                ])->all(),
        ];
    }

    /**
     * Placeholder photography until the media library lands (ADR-0006). Only images used on the page are sent.
     *
     * @return array<string, array{src: string, srcset: string, alt: string}>
     */
    private function media(array $sections, array $catalogue, string $locale): array
    {
        $ids = [];
        foreach ($sections as $s) {
            array_push($ids, $s['media'] ?? null, $s['media2'] ?? null, $s['style']['bgMedia'] ?? null,
                ...array_column($s['cards'], 'media'), ...array_column($s['slides'], 'media'));
        }
        array_push($ids, ...array_column($catalogue['services'], 'media'), ...array_column($catalogue['regions'], 'media'));

        return $this->mediaFor($ids, $locale);
    }

    /**
     * @param  array<int, string|null>  $ids
     * @return array<string, array{src: string, srcset: string, alt: string}>
     */
    private function mediaFor(array $ids, string $locale): array
    {
        $alts = Setting::group('media')['placeholders'] ?? [];
        $out = [];
        foreach (array_unique(array_filter($ids)) as $id) {
            if (! isset($alts[$id])) {
                continue;
            }
            // Each entry knows its folder and generated widths (hero photography has larger renditions).
            $base = '/media/'.($alts[$id]['folder'] ?? 'placeholders')."/$id";
            $widths = $alts[$id]['widths'] ?? [800, 1600];
            $out[$id] = [
                'src' => "$base-".$widths[intdiv(count($widths), 2)].'.webp',
                'srcset' => implode(', ', array_map(fn (int $w) => "$base-$w.webp {$w}w", $widths)),
                'alt' => $alts[$id]['alt'][$locale] ?? $alts[$id]['alt'][Locales::FALLBACK],
            ];
        }

        return $out;
    }
}
