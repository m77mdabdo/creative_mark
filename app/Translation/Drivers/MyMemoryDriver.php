<?php

namespace App\Translation\Drivers;

use App\Translation\MachineTranslator;
use App\Translation\TranslationUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** MyMemory public API: free fallback with a daily quota. One request per text (no batching). */
class MyMemoryDriver implements MachineTranslator
{
    private const MAX_CHARS = 500; // per request limit of the free API

    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'mymemory';
    }

    public function prefersShortText(): bool
    {
        return true;
    }

    public function translate(array $texts, string $from, string $to): array
    {
        return array_map(function (string $text) use ($from, $to) {
            if (mb_strlen($text) > self::MAX_CHARS) {
                throw new TranslationUnavailable('MyMemory accepts at most '.self::MAX_CHARS.' characters per text.');
            }

            try {
                $response = Http::timeout($this->config['timeout'])->get($this->config['url'], array_filter([
                    'q' => $text,
                    'langpair' => $this->code($from).'|'.$this->code($to),
                    'de' => $this->config['email'] ?? null,
                ]));
            } catch (ConnectionException $e) {
                throw new TranslationUnavailable("MyMemory is not reachable: {$e->getMessage()}", previous: $e);
            }

            if ($response->failed() || (int) $response->json('responseStatus') !== 200 || $response->json('quotaFinished')) {
                throw new TranslationUnavailable('MyMemory error: '.$response->json('responseDetails', $response->status()));
            }

            // Memory matches sometimes carry XLIFF tags (<g id="1">) or a trailing colon from their source segment.
            $text = strip_tags(html_entity_decode((string) $response->json('responseData.translatedText'), ENT_QUOTES | ENT_HTML5));

            return rtrim(trim($text), ':');
        }, array_values($texts));
    }

    private function code(string $locale): string
    {
        return $this->config['codes'][$locale] ?? $locale;
    }
}
