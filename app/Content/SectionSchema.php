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
    ];

    public const THEMES = ['dark', 'charcoal', 'ivory', 'white'];

    public const MOTION = ['none', 'fade-up', 'reveal-mask', 'zoom-in', 'parallax-slow', 'stagger-cards', 'counter', 'line-draw'];

    private const TEXT = ['eyebrow', 'title', 'highlight', 'subtitle', 'body'];

    private const KEYS = ['id', 'type', 'label', 'hidden', 'eyebrow', 'title', 'highlight', 'subtitle', 'body', 'media', 'media2', 'buttons', 'cards', 'limit', 'style', 'motion'];

    private const CARD_KEYS = ['id', 'title', 'text', 'icon', 'media', 'verified'];

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
                array_push($e, ...self::localeMap($s[$k], $k));
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
    private static function localeMap(mixed $v, string $field): array
    {
        if ($v === null) {
            return [];
        }
        if (! is_array($v) || ! isset($v['en'])) {
            return ["$field must be a locale map with English."];
        }
        $e = [];
        foreach ($v as $locale => $text) {
            if (! is_string($text) || mb_strlen($text) > 2000) {
                $e[] = "$field [$locale] must be text up to 2000 characters.";
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
