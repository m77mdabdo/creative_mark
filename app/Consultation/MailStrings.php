<?php

namespace App\Consultation;

use App\Support\Locales;

/** Email wording per language (lang/mail/{locale}.json), English filling any gap. */
final class MailStrings
{
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    /** @param array<string, string> $replace */
    public static function get(string $locale, string $key, array $replace = []): string
    {
        $strings = self::$cache[$locale] ??= [...self::load(Locales::FALLBACK), ...self::load($locale)];
        $text = $strings[$key] ?? $key;
        foreach ($replace as $name => $value) {
            $text = str_replace(":{$name}", $value, $text);
        }

        return $text;
    }

    /** @return array<string, string> */
    private static function load(string $locale): array
    {
        $file = lang_path("mail/{$locale}.json");

        return is_file($file) ? json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR) : [];
    }
}
