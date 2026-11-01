<?php

namespace App\Translation;

use App\Support\Locales;

/**
 * Interface labels (buttons, menus, notices) stored per locale in lang/ui/{locale}.json.
 * English is the master; keys a machine filled are listed in lang/ui/machine.json until reviewed.
 */
final class UiStrings
{
    /** @return array<string, string> */
    public static function load(string $locale): array
    {
        $file = self::path("{$locale}.json");

        return is_file($file) ? json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR) : [];
    }

    /** English first, then the locale on top: a missing key always has a label. */
    public static function forLocale(string $locale): array
    {
        return [...self::load(Locales::FALLBACK), ...self::load($locale)];
    }

    /** @return list<string> */
    public static function machineKeys(string $locale): array
    {
        $file = self::path('machine.json');

        return is_file($file) ? (json_decode(file_get_contents($file), true)[$locale] ?? []) : [];
    }

    /** @param list<string> $machineKeys */
    public static function save(string $locale, array $strings, array $machineKeys): void
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        file_put_contents(self::path("{$locale}.json"), json_encode($strings, $flags)."\n");

        $file = self::path('machine.json');
        $all = is_file($file) ? json_decode(file_get_contents($file), true) : [];
        $all[$locale] = $machineKeys;
        ksort($all);
        file_put_contents($file, json_encode($all, $flags)."\n");
    }

    private static function path(string $file): string
    {
        return rtrim(config('translation.ui_strings_path') ?: lang_path('ui'), '/')."/{$file}";
    }
}
