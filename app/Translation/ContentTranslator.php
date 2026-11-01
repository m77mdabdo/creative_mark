<?php

namespace App\Translation;

use App\Enums\ReviewStatus;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Fills missing languages of CMS content with machine translations (D-05).
 *
 * Rules:
 *  - The source is the first language in config('translation.source_priority') that has text (English, else Arabic),
 *    so content written only in Arabic reaches every language, and the other way round.
 *  - Only empty languages are filled. With $refresh, earlier *machine* text is redone; text a person wrote or
 *    approved ("review", "approved") is never overwritten.
 *  - Everything written here is marked "machine" and stays behind the review notice until approved.
 */
class ContentTranslator
{
    public function __construct(private Translator $translator) {}

    /**
     * Translates one record that uses App\Models\Concerns\HasTranslations.
     *
     * @param  list<string>|null  $targets
     * @return int number of languages written
     */
    public function translateRecord(Model $record, ?array $targets = null, bool $refresh = false): int
    {
        $rows = $record->translations()->get()->keyBy('locale');
        $fields = array_values(array_diff($record->translations()->getRelated()->getFillable(), ['locale', 'review_status', 'reviewed_at', 'slug']));
        $source = $this->sourceLocale(fn (string $locale) => $rows->has($locale) && collect($fields)->contains(fn ($f) => filled($rows[$locale]->{$f})));
        if (! $source) {
            return 0;
        }

        $written = 0;
        foreach ($this->targets($targets, $source) as $locale) {
            $existing = $rows->get($locale);
            // Text a person wrote stays; only its empty fields (e.g. a field added later) are filled.
            $onlyEmpty = $existing && ! $this->mayOverwrite($existing->review_status, $refresh);
            $todo = $onlyEmpty ? array_values(array_filter($fields, fn ($f) => blank($existing->{$f}) && filled($rows[$source]->{$f}))) : $fields;
            if ($todo === []) {
                continue;
            }

            $values = [];
            $sourceTexts = [];
            foreach ($todo as $field) {
                $value = $rows[$source]->{$field};
                $sourceTexts = [...$sourceTexts, ...(is_array($value) ? array_values($value) : [$value])];
                $values[$field] = match (true) {
                    is_string($value) && filled($value) => $this->translator->translateOne($value, $source, $locale),
                    is_array($value) => $this->translator->translate(array_values($value), $source, $locale),
                    default => $value,
                };
            }

            if ($onlyEmpty) {
                $existing->update($values);
            } else {
                // Professional (translation memory) text is "review"; anything machine-made is "machine".
                $status = $this->translator->coveredByMemory($sourceTexts, $source, $locale) ? ReviewStatus::Review : ReviewStatus::Machine;
                $record->translations()->updateOrCreate(['locale' => $locale], [...$values, 'review_status' => $status]);
            }
            $written++;
        }

        return $written;
    }

    /**
     * Fills missing languages inside the page's draft blocks (every {"en": …, "ar": …} map) and its title/SEO rows.
     * The live page changes only when the draft is published.
     *
     * @param  list<string>|null  $targets
     */
    public function translatePage(Page $page, ?array $targets = null, bool $refresh = false): int
    {
        $sections = $this->fillLocaleMaps($page->draft_sections, $targets, $refresh, $this->pageMachineLocales($page));

        return DB::transaction(function () use ($page, $sections, $targets, $refresh) {
            $page->forceFill(['draft_sections' => $sections])->save();

            return $this->translateRecord($page, $targets, $refresh);
        });
    }

    /**
     * Settings whose values are locale maps (navigation labels, offices, catalogue labels, image alt text).
     * Settings are not edited per language in the dashboard yet, so on refresh every language outside
     * the source languages (English, Arabic) is treated as machine text and redone.
     *
     * @param  list<string>|null  $targets
     */
    public function translateSettings(?array $targets = null, bool $refresh = false): int
    {
        $count = 0;
        foreach (Setting::query()->where('is_sensitive', false)->get() as $setting) {
            $value = $setting->value;
            if (! is_array($value)) {
                continue;
            }
            $machine = array_values(array_diff(Locales::codes(), config('translation.source_priority')));
            $filled = $this->fillLocaleMaps($value, $targets, $refresh, $machine);
            if ($filled !== $value) {
                $setting->update(['value' => $filled]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Interface labels (lang/ui/{locale}.json). Missing keys are machine translated from English.
     *
     * @param  list<string>|null  $targets
     */
    public function translateInterface(?array $targets = null, bool $refresh = false): int
    {
        $source = UiStrings::load('en');
        $count = 0;
        foreach ($this->targets($targets, 'en') as $locale) {
            $current = UiStrings::load($locale);
            $machine = UiStrings::machineKeys($locale);
            $missing = array_filter($source, fn ($v, $k) => ! isset($current[$k]) || ($refresh && in_array($k, $machine, true)), ARRAY_FILTER_USE_BOTH);
            if ($missing === []) {
                continue;
            }
            $translated = array_combine(array_keys($missing), $this->translator->translate(array_values($missing), 'en', $locale));
            $machineNow = array_keys(array_filter($missing, fn (string $text) => ! $this->translator->coveredByMemory([$text], 'en', $locale)));
            $stillMachine = array_diff($machine, array_keys($missing));
            UiStrings::save($locale, [...$current, ...$translated], array_values(array_unique([...$stillMachine, ...$machineNow])));
            $count += count($translated);
        }

        return $count;
    }

    /**
     * Walks any nested array and fills every locale map ({"en": "...", "ar": "..."}).
     *
     * @param  list<string>  $machineLocales  locales whose existing text may be redone when $refresh is on
     */
    private function fillLocaleMaps(array $data, ?array $targets, bool $refresh, array $machineLocales): array
    {
        if ($this->isLocaleMap($data)) {
            $source = $this->sourceLocale(fn (string $l) => filled($data[$l] ?? null));
            if (! $source) {
                return $data;
            }
            foreach ($this->targets($targets, $source) as $locale) {
                $redo = $refresh && in_array($locale, $machineLocales, true);
                if (blank($data[$locale] ?? null) || $redo) {
                    $data[$locale] = $this->translator->translateOne($data[$source], $source, $locale);
                }
            }

            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->fillLocaleMaps($value, $targets, $refresh, $machineLocales);
            }
        }

        return $data;
    }

    private function isLocaleMap(array $data): bool
    {
        return $data !== []
            && array_diff(array_keys($data), Locales::codes()) === []
            && (isset($data['en']) || isset($data['ar']))
            && collect($data)->every(fn ($v) => is_string($v) || $v === null);
    }

    /** Locales whose page text is machine-made (from page_translations), so a refresh may redo them. */
    private function pageMachineLocales(Page $page): array
    {
        return $page->translations()->where('review_status', ReviewStatus::Machine)->pluck('locale')->all();
    }

    private function sourceLocale(callable $hasText): ?string
    {
        foreach (config('translation.source_priority') as $locale) {
            if ($hasText($locale)) {
                return $locale;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function targets(?array $targets, string $source): array
    {
        return array_values(array_diff($targets ?: Locales::codes(), [$source]));
    }

    private function mayOverwrite(ReviewStatus $status, bool $refresh): bool
    {
        return $status === ReviewStatus::Missing || ($refresh && $status === ReviewStatus::Machine);
    }
}
