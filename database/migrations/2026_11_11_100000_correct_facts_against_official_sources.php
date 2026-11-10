<?php

use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fact check of 11 November 2026 against official and cited sources (docs/reviews/2026-11-11-fact-check.md):
 * Q2 2025 FDI growth was 14.5%, not 64.7% (now the 2025 annual GASTAT figure); MISA's Investor Guide gives
 * 5 working days for an investment registration, not "up to 10 days"; two news stories said more than their
 * sources. The corrections, in all six languages, are in database/seeders/data/corrections/2026-11-11-fact-check.json
 * (the seed files carry the same text). Live pages get a new published version; unpublished drafts are corrected
 * too but stay unpublished.
 */
return new class extends Migration
{
    private array $c;

    public function up(): void
    {
        $this->c = json_decode(file_get_contents(database_path('seeders/data/corrections/2026-11-11-fact-check.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () {
            foreach (Page::query()->with('publishedRevision')->get() as $page) {
                $draft = $this->fix($page->draft_sections ?? []);
                $live = $page->publishedRevision?->sections;
                $fixedLive = $live === null ? null : $this->fix($live);
                if ($fixedLive !== null && $fixedLive !== $live) {
                    $revision = $page->revisions()->create(['sections' => $fixedLive, 'kind' => 'publish', 'note' => 'Fact check 2026-11-11: corrected against official sources']);
                    $page->forceFill(['published_revision_id' => $revision->id, 'published_at' => now()]);
                }
                if ($draft !== ($page->draft_sections ?? [])) {
                    $page->forceFill(['draft_sections' => $draft, 'lock_version' => $page->lock_version + 1]);
                }
                $page->isDirty() && $page->save();
            }

            foreach (Faq::query()->with('translations')->get() as $faq) {
                foreach ($this->c['maps'] as $map) {
                    if ($faq->translation('en')?->answer === $map['old']) {
                        foreach ($map['new'] as $locale => $text) {
                            $faq->translations()->where('locale', $locale)->update(['answer' => $text]);
                        }
                    }
                }
            }

            foreach ($this->c['news'] as $slug => $fields) {
                $article = NewsArticle::query()->where('slug', $slug)->with('translations')->first();
                foreach ($article?->translations ?? [] as $t) {
                    $l = $t->locale;
                    isset($fields['excerpt'][$l]) && $t->excerpt = $fields['excerpt'][$l];
                    isset($fields['body'][$l]) && $t->body = $fields['body'][$l];
                    isset($fields['fact_values'][$l]) && $t->fact_values = $fields['fact_values'][$l];
                    if (isset($fields['first_paragraph'][$l]) && str_contains((string) $t->body, "\n")) {
                        $t->body = $fields['first_paragraph'][$l]."\n\n".ltrim(explode("\n", $t->body, 2)[1]);
                    }
                    if (isset($fields['fact_label_0'][$l])) {
                        $labels = $t->fact_labels ?? [];
                        $labels[0] = $fields['fact_label_0'][$l];
                        $t->fact_labels = $labels;
                    }
                    $t->save();
                }
            }
        });
    }

    /** Replaces corrected texts (every language) and the replaced source anywhere in a page's sections. */
    private function fix(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (isset($value['en']) && is_string($value['en'])) {
            foreach ($this->c['maps'] as $map) {
                if ($value['en'] === $map['old']) {
                    return $map['new'];
                }
            }
        }
        foreach ($this->c['sources'] as $source) {
            if (($value['href'] ?? null) === $source['old_href']) {
                return [...$value, 'href' => $source['href'], 'label' => $source['label'], 'publisher' => $source['publisher']];
            }
        }

        return array_map(fn ($v) => $this->fix($v), $value);
    }

    public function down(): void
    {
        // Corrections of wrong facts are not reverted; earlier page versions stay in the version history.
    }
};
