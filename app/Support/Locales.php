<?php

namespace App\Support;

/** Public locales (ADR-0005, D-04): URL prefix => BCP 47 tag, direction, native name. */
final class Locales
{
    public const DEFAULT = 'ar';

    public const FALLBACK = 'en';

    public const ALL = [
        'ar' => ['tag' => 'ar', 'dir' => 'rtl', 'native' => 'العربية'],
        'en' => ['tag' => 'en', 'dir' => 'ltr', 'native' => 'English'],
        'fr' => ['tag' => 'fr', 'dir' => 'ltr', 'native' => 'Français'],
        'hi' => ['tag' => 'hi', 'dir' => 'ltr', 'native' => 'हिन्दी'],
        'ur' => ['tag' => 'ur-PK', 'dir' => 'rtl', 'native' => 'اردو'],
        'zh' => ['tag' => 'zh-Hans', 'dir' => 'ltr', 'native' => '中文'],
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::ALL);
    }

    public static function dir(string $code): string
    {
        return self::ALL[$code]['dir'];
    }

    public static function tag(string $code): string
    {
        return self::ALL[$code]['tag'];
    }

    /** Picks the best locale from an Accept-Language header, or null. */
    public static function fromAcceptLanguage(?string $header): ?string
    {
        if (! $header) {
            return null;
        }
        $ranked = [];
        foreach (explode(',', $header) as $i => $part) {
            [$lang, $q] = array_pad(explode(';q=', trim($part)), 2, '1');
            $code = strtolower(substr(trim($lang), 0, 2));
            if (self::isSupported($code)) {
                $ranked[$code] = max($ranked[$code] ?? 0, (float) $q - $i / 1000);
            }
        }
        arsort($ranked);

        return array_key_first($ranked);
    }
}
