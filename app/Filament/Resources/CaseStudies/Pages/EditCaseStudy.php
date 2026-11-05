<?php

namespace App\Filament\Resources\CaseStudies\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseStudy extends EditRecord
{
    use SavesTranslations {
        mutateFormDataBeforeFill as fillTranslations;
    }

    protected static string $resource = CaseStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label(__('Preview'))->icon('heroicon-o-eye')->color('gray')
                ->url(fn () => route('admin.cases.preview', ['slug' => $this->getRecord()->slug, 'locale' => app()->getLocale()]), shouldOpenInNewTab: true),
            TranslateMissingLanguagesAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = $this->fillTranslations($data);
        foreach ($data['translations'] ?? [] as $locale => $fields) {
            $texts = $fields['step_texts'] ?? [];
            $data['translations'][$locale]['steps'] = array_map(fn ($title, $i) => ['title' => $title, 'text' => $texts[$i] ?? null], $fields['step_titles'] ?? [], array_keys($fields['step_titles'] ?? []));
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
