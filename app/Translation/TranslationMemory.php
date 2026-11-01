<?php

namespace App\Translation;

/**
 * Professional translations of the site's English master text, one file per locale
 * (database/seeders/data/translation-memory/{locale}.json, English => translation).
 * An exact match always wins over machine translation.
 */
final class TranslationMemory
{
    /** @var array<string, array<string, string>> */
    private array $loaded = [];

    public function __construct(private string $directory) {}

    public function find(string $text, string $from, string $to): ?string
    {
        if ($from !== 'en') {
            return null;
        }

        return $this->forLocale($to)[$text] ?? null;
    }

    /** @return array<string, string> */
    private function forLocale(string $locale): array
    {
        if (! isset($this->loaded[$locale])) {
            $file = "{$this->directory}/{$locale}.json";
            $this->loaded[$locale] = is_file($file) ? json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR) : [];
        }

        return $this->loaded[$locale];
    }
}
