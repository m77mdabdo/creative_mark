<?php

namespace App\Translation\Drivers;

use App\Translation\MachineTranslator;
use App\Translation\TranslationUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Self-hosted LibreTranslate (Argos models). Free, unlimited, and content never leaves the machine. */
class LibreTranslateDriver implements MachineTranslator
{
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'libretranslate';
    }

    public function prefersShortText(): bool
    {
        return false;
    }

    public function translate(array $texts, string $from, string $to): array
    {
        try {
            $response = Http::timeout($this->config['timeout'])->acceptJson()
                ->post(rtrim($this->config['url'], '/').'/translate', array_filter([
                    'q' => array_values($texts),
                    'source' => $this->code($from),
                    'target' => $this->code($to),
                    'format' => 'text',
                    'api_key' => $this->config['api_key'] ?? null,
                ]));
        } catch (ConnectionException $e) {
            throw new TranslationUnavailable("LibreTranslate is not reachable: {$e->getMessage()}", previous: $e);
        }

        $translated = $response->json('translatedText');
        if ($response->failed() || ! is_array($translated) || count($translated) !== count($texts)) {
            throw new TranslationUnavailable('LibreTranslate error: '.$response->status().' '.$response->json('error', ''));
        }

        return array_values($translated);
    }

    private function code(string $locale): string
    {
        return $this->config['codes'][$locale] ?? $locale;
    }
}
