<?php

namespace App\Drafts;

/** Knows how to make one kind of draft live. Implementations write to the live tables only, with no other side effects. */
interface DraftTarget
{
    /** @param array<string, mixed> $payload */
    public function apply(array $payload): void;
}
