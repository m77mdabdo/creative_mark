<?php

namespace App\Drafts\Targets;

use App\Content\CatalogueSync;
use App\Drafts\DraftTarget;

/** The figures or client reviews edited from a page editor: payload {kind, items}. */
final class CatalogueTarget implements DraftTarget
{
    public function __construct(private readonly CatalogueSync $catalogue) {}

    public function apply(array $payload): void
    {
        $this->catalogue->sync($payload['kind'], $payload['items'] ?? []);
    }
}
