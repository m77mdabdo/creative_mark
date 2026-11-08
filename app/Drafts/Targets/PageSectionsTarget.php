<?php

namespace App\Drafts\Targets;

use App\Content\PublishPage;
use App\Drafts\DraftTarget;
use App\Models\Page;

/** A page whose saved sections differ from the live version: payload {page}. The sections live on the page itself. */
final class PageSectionsTarget implements DraftTarget
{
    public function apply(array $payload): void
    {
        $page = Page::query()->where('key', $payload['page'])->with('publishedRevision')->first();
        if ($page?->hasUnpublishedChanges()) {
            (new PublishPage)($page, auth()->id(), 'Published with the unpublished changes');
        }
    }
}
