<?php

namespace App\Content;

use Illuminate\Validation\ValidationException;

/**
 * Server-side contract for page blocks (ADR-0004, SEC-22, SEC-25).
 * Unknown types or fields, HTML in text, unsafe links and out-of-range style values are rejected.
 */
final class SectionSchema
{
    public const TYPES = [
        'hero', 'whyInvest', 'journey', 'countries', 'sectors', 'trackRecord',
        'services', 'map', 'faq', 'cta', 'testimonials',
        'statement', 'imageText', 'cards', 'serviceGroups', 'processSteps', 'comparison', 'regionGrid',
        'guideNav', 'guideChapter', 'checklist', 'sourceList', 'newsLead', 'newsGrid', 'caseGrid',
        'faqLibrary', 'contactInfo', 'legalText', 'serviceOverview', 'relatedServices', 'regionOverview', 'moreRegions',
    ];

    public const LAYOUTS = ['split', 'split-reverse'];

    public const THEMES = ['dark', 'charcoal', 'ivory', 'white'];

    public const MOTION = ['none', 'fade-up', 'reveal-mask', 'zoom-in', 'parallax-slow', 'stagger-cards', 'counter', 'line-draw'];

    private const TEXT = ['eyebrow', 'title', 'highlight', 'subtitle', 'body', 'points', 'note'];

    private const KEYS = ['id', 'type', 'label', 'hidden', 'eyebrow', 'title', 'highlight', 'subtitle', 'body', 'media', 'media2', 'buttons', 'cards', 'slides', 'limit', 'layout', 'style', 'motion', 'points', 'note', 'sources'];

    private const CARD_KEYS = ['id', 'title', 'text', 'icon', 'media', 'verified'];

    /** Hero slideshow: each slide has its own image and copy; buttons stay with the section. */
    private const SLIDE_KEYS = ['id', 'media', 'eyebrow', 'title', 'highlight', 'subtitle', 'region'];

    private const MAX_SLIDES = 10;

    /** Cited references (investor guide): a named publisher, a title and an https link. */
    private const SOURCE_KEYS = ['id', 'label', 'publisher', 'href', 'date'];

    private const MAX_SOURCES = 30;

    /** @param  list<array<string, mixed>>  $sections */
    public static function validate(array $sections): void
    {
        $errors = [];
        foreach (array_values($sections) as $i => $s) {
            foreach (self::check($s) as $e) {
                $errors["sections.$i"][] = $e;
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @return list<string> */
    private static function check(mixed $s): array
    {
        if (! is_array($s)) {
            return ['Section must be an object.'];
        }
        $e = [];
        if (! in_array($s['type'] ?? null, self::TYPES, true)) {
            $e[] = 'Unknown section type.';
        }
        if (! is_string($s['id'] ?? null) || ! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $s['id'])) {
            $e[] = 'Invalid id.';
        }
        foreach (array_diff(array_keys($s), self::KEYS) as $k) {
            $e[] = "Unknown field [$k].";
        }
        foreach (self::TEXT as $k) {
            if (isset($s[$k])) {
                // Legal pages carry one long body of plain text with "## " headings.
                $max = ($s['type'] ?? null) === 'legalText' && $k === 'body' ? 12000 : 2000;
                array_push($e, ...self::localeMap($s[$k], $k, $max));
            }
        }
        foreach (['media', 'media2'] as $k) {
            if (isset($s[$k]) && ! self::mediaKey($s[$k])) {
                $e[] = "Invalid $k.";
            }
        }
        foreach ($s['buttons'] ?? [] as $b) {
            array_push($e, ...self::localeMap($b['label'] ?? null, 'button label'));
            if (! self::safeHref($b['href'] ?? '')) {
                $e[] = 'Unsafe button link.';
            }
            if (! in_array($b['style'] ?? null, ['gold', 'outline', 'link'], true)) {
                $e[] = 'Invalid button style.';
            }
        }
        foreach ($s['cards'] ?? [] as $c) {
            foreach (array_diff(array_keys((array) $c), self::CARD_KEYS) as $k) {
                $e[] = "Unknown card field [$k].";
            }
            array_push($e, ...self::localeMap($c['title'] ?? null, 'card title'), ...self::localeMap($c['text'] ?? null, 'card text'));
            if (isset($c['media']) && ! self::mediaKey($c['media'])) {
                $e[] = 'Invalid card media.';
            }
        }
        if (isset($s['layout']) && ! in_array($s['layout'], self::LAYOUTS, true)) {
            $e[] = 'Invalid layout.';
        }
        $slides = $s['slides'] ?? [];
        if (! is_array($slides) || count($slides) > self::MAX_SLIDES) {
            $e[] = 'At most '.self::MAX_SLIDES.' slides.';
            $slides = [];
        }
        foreach ($slides as $slide) {
            foreach (array_diff(array_keys((array) $slide), self::SLIDE_KEYS) as $k) {
                $e[] = "Unknown slide field [$k].";
            }
            if (isset($slide['region']) && ! self::mediaKey($slide['region'])) {
                $e[] = 'Invalid slide region.';
            }
            if (! self::mediaKey($slide['media'] ?? null)) {
                $e[] = 'Every slide needs an image.';
            }
            foreach (['eyebrow', 'title', 'highlight', 'subtitle'] as $k) {
                array_push($e, ...self::localeMap($slide[$k] ?? null, "slide $k"));
            }
        }
        $sources = $s['sources'] ?? [];
        if (! is_array($sources) || count($sources) > self::MAX_SOURCES) {
            $e[] = 'At most '.self::MAX_SOURCES.' sources.';
            $sources = [];
        }
        foreach ($sources as $source) {
            foreach (array_diff(array_keys((array) $source), self::SOURCE_KEYS) as $k) {
                $e[] = "Unknown source field [$k].";
            }
            array_push($e, ...self::localeMap($source['label'] ?? null, 'source label'), ...self::localeMap($source['publisher'] ?? null, 'source publisher'));
            // External references must be secure absolute links.
            if (! preg_match('~^https://[^\s/]+\.[^\s]+$~i', (string) ($source['href'] ?? ''))) {
                $e[] = 'Source links must be https URLs.';
            }
            if (isset($source['date']) && ! preg_match('/^\d{4}(-\d{2}){0,2}$/', (string) $source['date'])) {
                $e[] = 'Source date must be YYYY, YYYY-MM or YYYY-MM-DD.';
            }
        }
        $style = $s['style'] ?? [];
        if (! in_array($style['theme'] ?? null, self::THEMES, true)) {
            $e[] = 'Invalid theme.';
        }
        if (isset($style['overlay']) && ($style['overlay'] < 0 || $style['overlay'] > 90)) {
            $e[] = 'Overlay must be 0-90.';
        }
        if (isset($style['bgMedia']) && ! self::mediaKey($style['bgMedia'])) {
            $e[] = 'Invalid background media.';
        }
        if (! in_array($style['padding'] ?? 'md', ['sm', 'md', 'lg'], true) || ! in_array($style['align'] ?? 'start', ['start', 'center'], true)) {
            $e[] = 'Invalid padding or alignment.';
        }
        $m = $s['motion'] ?? ['preset' => 'fade-up'];
        if (! in_array($m['preset'] ?? null, self::MOTION, true)
            || ($m['intensity'] ?? 100) < 0 || ($m['intensity'] ?? 100) > 100
            || ($m['delay'] ?? 0) < 0 || ($m['delay'] ?? 0) > 600) {
            $e[] = 'Invalid motion settings.';
        }

        return $e;
    }

    /** @return list<string> */
    private static function localeMap(mixed $v, string $field, int $max = 2000): array
    {
        if ($v === null) {
            return [];
        }
        if (! is_array($v) || ! isset($v['en'])) {
            return ["$field must be a locale map with English."];
        }
        $e = [];
        foreach ($v as $locale => $text) {
            if (! is_string($text) || mb_strlen($text) > $max) {
                $e[] = "$field [$locale] must be text up to $max characters.";
            } elseif ($text !== strip_tags($text)) {
                $e[] = "$field [$locale] must not contain HTML.";
            }
        }

        return $e;
    }

    public static function safeHref(string $href): bool
    {
        return (bool) preg_match('~^(/(?!/)[^\s]*|#[\w-]*|https://[^\s]+|mailto:[^\s]+|tel:\+?[\d\s-]+)$~i', trim($href));
    }

    private static function mediaKey(mixed $v): bool
    {
        return is_string($v) && (bool) preg_match('/^[a-z0-9-]{1,100}$/', $v);
    }
}
