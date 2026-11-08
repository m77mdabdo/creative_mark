<?php

use App\Drafts\Draft;
use App\Drafts\Targets\PageSectionsTarget;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/** Pages that already had unpublished sections before drafts were listed appear in the list of unpublished changes. */
return new class extends Migration
{
    public function up(): void
    {
        Page::query()->with(['publishedRevision', 'translations'])->get()
            ->filter(fn (Page $page) => $page->publishedRevision && $page->hasUnpublishedChanges())
            ->each(fn (Page $page) => Draft::query()->firstOrCreate(['key' => 'page:'.$page->key], [
                'target' => PageSectionsTarget::class, 'payload' => ['page' => $page->key],
                // The dashboard's default language is Arabic.
                'label' => __('Page', [], 'ar').': '.($page->t('title', 'ar') ?? $page->key), 'edit_url' => $page->editorPath(),
            ]));
    }

    public function down(): void
    {
        Draft::query()->where('target', PageSectionsTarget::class)->delete();
    }
};
