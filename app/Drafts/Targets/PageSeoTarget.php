<?php

namespace App\Drafts\Targets;

use App\Content\PageSeoWriter;
use App\Drafts\DraftTarget;
use App\Models\Page;

/** A page's name and Google texts: payload {page, seo}. The sections have their own draft on the page itself. */
final class PageSeoTarget implements DraftTarget
{
    public function __construct(private readonly PageSeoWriter $writer) {}

    public function apply(array $payload): void
    {
        if ($page = Page::query()->where('key', $payload['page'])->first()) {
            $this->writer->write($page, $payload['seo'] ?? []);
        }
    }
}
