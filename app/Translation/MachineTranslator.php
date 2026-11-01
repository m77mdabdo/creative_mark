<?php

namespace App\Translation;

/** A machine translation backend. Implementations translate plain text only (never HTML). */
interface MachineTranslator
{
    /**
     * @param  list<string>  $texts
     * @return list<string> same order and length as $texts
     *
     * @throws TranslationUnavailable when the backend cannot serve the request
     */
    public function translate(array $texts, string $from, string $to): array;

    public function name(): string;

    /** Translation-memory backends are better at short labels than at sentences. */
    public function prefersShortText(): bool;
}
