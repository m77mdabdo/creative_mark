<?php

namespace App\Filament\Resources\CaseStudies\Pages;

use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCaseStudy extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = CaseStudyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stepsToLists($data);
    }

    /** The form edits steps as pairs; the record keeps two parallel lists. */
    private function stepsToLists(array $data): array
    {
        foreach ($data['translations'] ?? [] as $locale => $fields) {
            if (array_key_exists('steps', $fields)) {
                $steps = array_values((array) $fields['steps']);
                $data['translations'][$locale]['step_titles'] = array_map(fn ($s) => trim((string) ($s['title'] ?? '')), $steps);
                $data['translations'][$locale]['step_texts'] = array_map(fn ($s) => trim((string) ($s['text'] ?? '')), $steps);
                unset($data['translations'][$locale]['steps']);
            }
        }

        return $data;
    }
}
