<?php

namespace Tests\Support;

use App\Translation\MachineTranslator;
use App\Translation\TranslationUnavailable;

/** Deterministic translator for tests: "[fr] text". Can be told to fail or to mangle glossary tokens. */
class FakeMachineTranslator implements MachineTranslator
{
    public array $calls = [];

    public function __construct(private string $name = 'fake', private bool $down = false, private bool $mangleTokens = false, private bool $short = false) {}

    public function translate(array $texts, string $from, string $to): array
    {
        if ($this->down) {
            throw new TranslationUnavailable("{$this->name} is down");
        }
        $this->calls[] = $texts;

        return array_map(fn ($t) => "[{$to}] ".($this->mangleTokens ? str_replace('ZQX', 'ZQ X', $t) : $t), $texts);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function prefersShortText(): bool
    {
        return $this->short;
    }
}
