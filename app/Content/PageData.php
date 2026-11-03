<?php

namespace App\Content;

use App\Enums\ReviewStatus;
use App\Models\CaseStudy;
use App\Models\Country;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Region;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Support\Locales;
use App\Translation\UiStrings;

/**
 * Builds the props of a CMS page (home, about…) from its published revision and the catalogue.
 * Everything filtered by a content rule is removed here, so it never reaches the browser.
 */
final class PageData
{
    /** @return array<string, mixed> */
    /** @param bool $draft true renders the unpublished draft (dashboard preview only) */
    public function forPage(string $key, string $locale, bool $draft = false): array
    {
        $page = Page::query()->where('key', $key)->withLocale($locale)->with('publishedRevision')->firstOrFail();
        abort_unless($draft || $page->publishedRevision, 404);

        $faqLimit = 6;
        $sections = [];
        foreach ($draft ? $page->draft_sections : $page->publishedRevision->sections as $s) {
            if (! empty($s['hidden'])) {
                continue; // FR-021: hidden sections are absent from the HTML
            }
            if ($s['type'] === 'faq') {
                $faqLimit = (int) ($s['limit'] ?? 6);
            }
            $sections[] = $this->localizeSection($s, $locale);
        }

        $catalogue = $this->catalogue($locale, $faqLimit);
        if (collect($sections)->contains(fn ($s) => str_starts_with($s['type'], 'news'))) {
            $catalogue['articles'] = $this->articles(NewsArticle::query()->live()->withLocale($locale)->get(), $locale);
        }
        if (collect($sections)->contains(fn ($s) => $s['type'] === 'faqLibrary')) {
            $catalogue['faqLibrary'] = $this->faqLibrary($locale);
        }
        if (collect($sections)->contains(fn ($s) => $s['type'] === 'caseGrid')) {
            $catalogue['cases'] = $this->cases(CaseStudy::query()->live()->withLocale($locale)->get(), $locale);
        }
        // A business solution's own page: its questions, related solutions and a breadcrumb under Business solutions.
        $extra = [];
        if (str_starts_with($key, 'service-')) {
            $service = Service::query()->where('slug', substr($key, 8))->withLocale($locale)
                ->with(['faqs' => fn ($q) => $q->where('status', 'published')->where('needs_review', false)->orderBy('sort_order')->withLocale($locale)])->first();
            if ($service) {
                $bySlug = collect($catalogue['services'])->keyBy('slug');
                $catalogue['faqs'] = $service->faqs->map(fn (Faq $f) => ['id' => $f->id, 'question' => $f->t('question', $locale), 'answer' => $f->t('answer', $locale)])->all();
                $extra['related'] = collect($service->related ?? [])->map(fn ($s) => $bySlug[$s] ?? null)->filter()->values()->all();
                $extra['serviceSlug'] = $service->slug;
                // Case studies on a solution's page are the ones that used it.
                if (isset($catalogue['cases'])) {
                    $catalogue['cases'] = array_values(array_filter($catalogue['cases'], fn ($c) => in_array($service->slug, $c['services'] ?? [], true)));
                }
                $solutions = collect(Setting::group('navigation')['header'] ?? [])->firstWhere('href', '/services')['label'] ?? [];
                $extra['breadcrumbs'] = [
                    ['label' => $solutions[$locale] ?? $solutions[Locales::FALLBACK] ?? UiStrings::forLocale($locale)['allServices'], 'href' => '/services'],
                    ['label' => $service->t('title', $locale)],
                ];
            }
        }
        $sections = array_values(array_filter($sections, fn ($s) => match ($s['type']) {
            'trackRecord' => $catalogue['stats'] !== [],
            'testimonials' => $catalogue['testimonials'] !== [],
            'faq' => $catalogue['faqs'] !== [],
            'relatedServices' => ($extra['related'] ?? []) !== [],
            'caseGrid' => ($catalogue['cases'] ?? []) !== [],
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
            ...$extra,
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

    /**
     * A region's detail page: the region from the catalogue, its recommended services and three neighbours
     * in catalogue order for further exploration.
     *
     * @return array<string, mixed>
     */
    public function forRegion(string $slug, string $locale): array
    {
        $catalogue = $this->catalogue($locale, 0);
        $regions = collect($catalogue['regions']);
        $index = $regions->search(fn ($r) => $r['slug'] === $slug);
        abort_if($index === false, 404);
        $region = $regions[$index];
        $services = collect($catalogue['services'])->keyBy('slug');
        $related = collect($region['services'])->map(fn ($s) => $services[$s] ?? null)->filter()->values()->all();
        $others = collect(range(1, 3))->map(fn ($i) => $regions[($index + $i) % $regions->count()])->all();
        $page = Page::query()->where('key', 'regions')->withLocale($locale)->first();

        return [
            'pageKey' => 'region',
            'title' => $region['name'],
            'regionsTitle' => $page?->t('title', $locale) ?? 'Regions',
            'seo' => [
                'title' => "{$region['name']} | {$region['bestFor']} | Creative Mark",
                'description' => mb_strimwidth((string) $region['description'], 0, 300, '…'),
            ],
            'reviewStatus' => ReviewStatus::Approved->value,
            'region' => $region,
            'industries' => $catalogue['industries'],
            'related' => $related,
            'otherRegions' => $others,
            'media' => $this->mediaFor([$region['media'], ...array_column($related, 'media'), ...array_column($others, 'media'), 'about-kingdom-centre'], $locale),
        ];
    }

    /**
     * One news article with up to three related articles (same category first, then the latest).
     *
     * @return array<string, mixed>
     */
    public function forArticle(string $slug, string $locale): array
    {
        $all = NewsArticle::query()->live()->withLocale($locale)->get();
        $article = $all->firstWhere('slug', $slug);
        abort_unless($article, 404);
        $others = $all->reject(fn ($a) => $a->is($article));
        $related = $others->where('category', $article->category)->merge($others->where('category', '!=', $article->category))->take(3);
        [$item] = $this->articles(collect([$article]), $locale, withBody: true);
        $page = Page::query()->where('key', 'news')->withLocale($locale)->first();

        return [
            'pageKey' => 'article',
            'title' => $item['title'],
            'newsTitle' => $page?->t('title', $locale) ?? 'News',
            'seo' => ['title' => "{$item['title']} | Creative Mark", 'description' => $item['excerpt']],
            'reviewStatus' => ($article->translation($locale)?->review_status ?? ReviewStatus::Missing)->value,
            'article' => $item,
            'related' => $this->articles($related, $locale),
            'latest' => array_map(fn ($a) => ['slug' => $a['slug'], 'title' => $a['title']], $this->articles($others->take(6), $locale)),
            'media' => $this->mediaFor([$article->media, ...$related->pluck('media')->all(), 'news-corniche'], $locale),
        ];
    }

    /**
     * A case study with its region, services (from the catalogue) and two more cases.
     *
     * @return array<string, mixed>
     */
    public function forCaseStudy(string $slug, string $locale): array
    {
        $all = CaseStudy::query()->live()->withLocale($locale)->get();
        $case = $all->firstWhere('slug', $slug);
        abort_unless($case, 404);
        $catalogue = $this->catalogue($locale, 0);
        [$item] = $this->cases(collect([$case]), $locale, withStory: true);
        $services = collect($catalogue['services'])->keyBy('slug');
        $related = collect($case->services ?? [])->map(fn ($s) => $services[$s] ?? null)->filter()->values()->all();
        $others = $this->cases($all->reject(fn ($c) => $c->is($case))->take(2), $locale);
        $page = Page::query()->where('key', 'case-studies')->withLocale($locale)->first();

        return [
            'pageKey' => 'case',
            'title' => $item['title'],
            'casesTitle' => $page?->t('title', $locale) ?? 'Case studies',
            'seo' => ['title' => "{$item['title']} | Creative Mark", 'description' => $item['summary']],
            'reviewStatus' => ($case->translation($locale)?->review_status ?? ReviewStatus::Missing)->value,
            'caseStudy' => $item,
            'region' => collect($catalogue['regions'])->firstWhere('slug', $case->region),
            'regions' => $catalogue['regions'],
            'industries' => $catalogue['industries'],
            'countries' => $catalogue['countries'],
            'related' => $related,
            'otherCases' => $others,
            'media' => $this->mediaFor([$case->media, ...array_column($related, 'media'), ...array_column($others, 'media'), 'case-dubai'], $locale),
        ];
    }

    /**
     * @param  iterable<CaseStudy>  $cases
     * @return list<array<string, mixed>>
     */
    private function cases(iterable $cases, string $locale, bool $withStory = false): array
    {
        $out = [];
        foreach ($cases as $c) {
            $t = $c->translation($locale);
            $titles = $t?->step_titles ?? [];
            $texts = $t?->step_texts ?? [];
            $out[] = [
                'slug' => $c->slug, 'sector' => $c->sector, 'origin' => $c->origin, 'region' => $c->region, 'media' => $c->media,
                'services' => $c->services ?? [], 'illustrative' => $c->illustrative,
                'title' => $c->t('title', $locale), 'summary' => $c->t('summary', $locale),
                // RULE-02: a real client is named only with recorded consent; otherwise the neutral description is used.
                'client' => $c->t('client', $locale),
                ...($withStory ? [
                    'entity' => $c->t('entity', $locale), 'challenge' => $c->t('challenge', $locale), 'outcome' => $c->t('outcome', $locale),
                    'steps' => count($titles) === count($texts) ? array_map(fn ($a, $b) => ['title' => $a, 'text' => $b], $titles, $texts) : [],
                    'delivered' => $t?->delivered ?? [],
                ] : []),
            ];
        }

        return $out;
    }

    /**
     * @param  iterable<NewsArticle>  $articles
     * @return list<array<string, mixed>>
     */
    private function articles(iterable $articles, string $locale, bool $withBody = false): array
    {
        $out = [];
        foreach ($articles as $a) {
            $t = $a->translation($locale);
            $values = $t?->fact_values ?? [];
            $labels = $t?->fact_labels ?? [];
            $out[] = [
                'slug' => $a->slug, 'category' => $a->category, 'media' => $a->media, 'featured' => $a->featured,
                'date' => $a->published_on->toDateString(), 'source' => ['name' => $a->source_name, 'url' => $a->source_url],
                'title' => $a->t('title', $locale), 'excerpt' => $a->t('excerpt', $locale),
                ...($withBody ? [
                    'body' => $a->t('body', $locale), 'takeaway' => $a->t('takeaway', $locale),
                    // Facts are shown only when both lists line up.
                    'facts' => count($values) === count($labels) ? array_map(fn ($v, $l) => ['value' => $v, 'label' => $l], $values, $labels) : [],
                ] : []),
            ];
        }

        return $out;
    }

    /** @param array<string, mixed> $s */
    private function localizeSection(array $s, string $locale): array
    {
        $pick = fn (?array $map) => $map === null ? null : (filled($map[$locale] ?? null) ? $map[$locale] : $map[Locales::FALLBACK]);

        foreach (['eyebrow', 'title', 'highlight', 'subtitle', 'body', 'points', 'note'] as $k) {
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
        $s['sources'] = array_map(fn ($src) => [...$src, 'label' => $pick($src['label']), 'publisher' => $pick($src['publisher'] ?? null)], $s['sources'] ?? []);
        unset($s['label']);

        return $s;
    }

    /**
     * Every published, reviewed question with its category, for the FAQ page.
     *
     * @return array{categories: list<array{key: string, label: string}>, items: list<array<string, mixed>>}
     */
    private function faqLibrary(string $locale): array
    {
        $labels = Setting::group('catalogue')['faq_categories'] ?? [];
        $items = Faq::query()->where('status', 'published')->where('needs_review', false)->orderBy('sort_order')->withLocale($locale)->get()
            ->map(fn (Faq $m) => ['id' => $m->id, 'category' => $m->category, 'question' => $m->t('question', $locale), 'answer' => $m->t('answer', $locale)])->all();
        $used = array_unique(array_column($items, 'category'));

        return [
            'categories' => array_values(array_map(fn ($key) => ['key' => $key, 'label' => $labels[$key][$locale] ?? $labels[$key][Locales::FALLBACK] ?? $key],
                array_values(array_filter(array_keys($labels), fn ($k) => in_array($k, $used, true))))),
            'items' => $items,
        ];
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
                ->map(fn (Stat $m) => ['key' => $m->key, 'value' => $m->value, 'icon' => $m->icon, 'group' => $m->group, 'label' => $m->t('label', $locale)])->all(),
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
        array_push($ids, ...array_column($catalogue['services'], 'media'), ...array_column($catalogue['regions'], 'media'), ...array_column($catalogue['articles'] ?? [], 'media'), ...array_column($catalogue['cases'] ?? [], 'media'));
        // Section backgrounds may name any library image, not only those referenced by content.

        return $this->mediaFor($ids, $locale);
    }

    /**
     * @param  array<int, string|null>  $ids
     * @return array<string, array{src: string, srcset: string, alt: string}>
     */
    public function mediaFor(array $ids, string $locale): array
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
