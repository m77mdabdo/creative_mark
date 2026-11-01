<?php

namespace App\Content;

use App\Models\Page;
use App\Models\PageRevision;
use Illuminate\Support\Facades\DB;

/** Publishing turns the validated draft into an immutable revision and makes it live (ADR-0004, FR-120). */
final class PublishPage
{
    public function __invoke(Page $page, ?int $userId = null, string $note = 'Publish'): PageRevision
    {
        SectionSchema::validate($page->draft_sections);

        return DB::transaction(function () use ($page, $userId, $note) {
            $revision = $page->revisions()->create([
                'sections' => $page->draft_sections,
                'kind' => 'publish',
                'note' => $note,
                'created_by' => $userId,
            ]);
            $page->forceFill([
                'published_revision_id' => $revision->id,
                'status' => 'published',
                'published_at' => now(),
            ])->save();

            return $revision;
        });
    }
}
