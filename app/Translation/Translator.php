<?php

namespace App\Translation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Entry point for machine translation. Adds what the raw backends do not do:
 *  - fallback across drivers (config translation.drivers),
 *  - a phrasebook of preferred wording for recurring site terms,
 *  - glossary protection so brand and place names are never translated,
 *  - punctuation clean-up for Devanagari, Urdu and CJK output,
 *  - a cache, so unchanged text is never sent twice.
 */
class Translator
{
    /** @param list<MachineTranslator> $drivers */
    public function __construct(private array $drivers, private array $glossary, private int $cacheTtl, private array $phrasebook = []) {}

    /**
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function translate(array $texts, string $from, string $to): array
    {
        if ($from === $to || $texts === []) {
            return $texts;
        }

        $results = [];
        $pending = [];
        foreach (array_values($texts) as $i => $text) {
            if (trim($text) === '') {
                $results[$i] = $text;
            } elseif (isset($this->phrasebook[$text][$to])) {
                $results[$i] = $this->phrasebook[$text][$to];
            } elseif (($hit = Cache::get($this->cacheKey($text, $from, $to))) !== null) {
                $results[$i] = $hit;
            } else {
                $pending[$i] = $text;
            }
        }

        if ($pending !== []) {
            foreach ($this->translateWithGlossary($pending, $from, $to) as $i => $translated) {
                $results[$i] = $translated;
                Cache::put($this->cacheKey($pending[$i], $from, $to), $translated, $this->cacheTtl);
            }
        }

        ksort($results);

        return array_values($results);
    }

    public function translateOne(string $text, string $from, string $to): string
    {
        return $this->translate([$text], $from, $to)[0];
    }

    /**
     * Glossary terms become opaque tokens (ZQX0, ZQX1…) that translators leave alone. If a backend mangles
     * a token, that text is translated again piece by piece around the terms.
     *
     * @param  array<int, string>  $texts
     * @return array<int, string>
     */
    private function translateWithGlossary(array $texts, string $from, string $to): array
    {
        $terms = $this->termsIn($texts);
        $tokenized = array_map(fn (string $t) => $this->tokenize($t, $terms), $texts);
        $translated = array_combine(array_keys($texts), $this->call(array_values($tokenized), $from, $to));

        foreach ($translated as $i => $text) {
            $expected = $this->tokensIn($tokenized[$i]);
            $translated[$i] = $expected === $this->tokensIn($text)
                ? $this->detokenize($text, $terms, $to)
                : $this->translateAroundTerms($texts[$i], $terms, $from, $to);
            $translated[$i] = $this->tidy($translated[$i], $to);
        }

        return $translated;
    }

    /** Fallback when tokens did not survive: translate the text between glossary terms separately. */
    private function translateAroundTerms(string $text, array $terms, string $from, string $to): string
    {
        $pattern = '/('.implode('|', array_map(fn ($t) => preg_quote($t, '/'), $terms)).')/u';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [$text];
        $prose = array_values(array_filter($parts, fn ($p) => ! in_array($p, $terms, true) && trim($p) !== ''));
        $done = $prose ? array_combine($prose, $this->call($prose, $from, $to)) : [];

        return implode(' ', array_map(fn ($p) => in_array($p, $terms, true) ? $this->termFor($p, $to) : trim($done[$p] ?? $p), $parts));
    }

    /**
     * Short labels (menus, headings, chips) go to translation-memory drivers first, which know common terms
     * ("News", "Our edge") far better than sentence models; longer text goes to the sentence model first.
     *
     * @param  list<string>  $texts
     */
    private function call(array $texts, string $from, string $to): array
    {
        $short = array_filter($texts, fn (string $t) => $this->isShortLabel($t));
        $long = array_diff_key($texts, $short);
        $results = [];
        foreach ([[$short, true], [$long, false]] as [$group, $preferMemory]) {
            if ($group === []) {
                continue;
            }
            $drivers = $preferMemory ? $this->memoryFirst() : $this->drivers;
            $results += array_combine(array_keys($group), $this->callDrivers($drivers, array_values($group), $from, $to));
        }
        ksort($results);

        return array_values($results);
    }

    private function isShortLabel(string $text): bool
    {
        return mb_strlen($text) <= 60 && count(preg_split('/\s+/u', trim($text))) <= 6 && ! preg_match('/[.!?؟。]$/u', trim($text));
    }

    /** @return list<MachineTranslator> */
    private function memoryFirst(): array
    {
        $memory = array_filter($this->drivers, fn (MachineTranslator $d) => $d->prefersShortText());

        return [...$memory, ...array_filter($this->drivers, fn (MachineTranslator $d) => ! $d->prefersShortText())];
    }

    /**
     * @param  list<MachineTranslator>  $drivers
     * @param  list<string>  $texts
     */
    private function callDrivers(array $drivers, array $texts, string $from, string $to): array
    {
        $errors = [];
        foreach ($drivers as $driver) {
            try {
                return $driver->translate($texts, $from, $to);
            } catch (TranslationUnavailable $e) {
                $errors[] = $driver->name().': '.$e->getMessage();
                Log::warning('Machine translation driver failed', ['driver' => $driver->name(), 'error' => $e->getMessage()]);
            }
        }

        throw new TranslationUnavailable('No translation driver available. '.implode(' | ', $errors));
    }

    /** @return list<string> longest first, so "Creative Mark" wins over shorter overlapping terms */
    private function termsIn(array $texts): array
    {
        $joined = implode("\n", $texts);
        $terms = array_values(array_filter(array_keys($this->glossary), fn (string $term) => str_contains($joined, $term)));
        usort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $terms;
    }

    private function tokenize(string $text, array $terms): string
    {
        foreach ($terms as $i => $term) {
            $text = str_replace($term, "ZQX{$i}", $text);
        }

        return $text;
    }

    private function detokenize(string $text, array $terms, string $to): string
    {
        // Highest index first so ZQX1 does not eat the start of ZQX10
        foreach (array_reverse($terms, true) as $i => $term) {
            $text = str_replace("ZQX{$i}", $this->termFor($term, $to), $text);
        }

        return $text;
    }

    private function termFor(string $term, string $locale): string
    {
        return $this->glossary[$term][$locale] ?? $term;
    }

    /** @return list<string> */
    private function tokensIn(string $text): array
    {
        preg_match_all('/ZQX\d+/', $text, $m);
        sort($m[0]);

        return $m[0];
    }

    /** Removes artefacts such as "।." and "۔." and stray spaces before punctuation. */
    private function tidy(string $text, string $to): string
    {
        $text = preg_replace('/([।۔。])\.+/u', '$1', $text);
        $text = preg_replace('/\s+([,.;:!?،؛])/u', '$1', $text);

        return trim(preg_replace('/[ \t]{2,}/u', ' ', $text));
    }

    private function cacheKey(string $text, string $from, string $to): string
    {
        return 'mt:'.$from.':'.$to.':'.hash('xxh128', $text);
    }
}
