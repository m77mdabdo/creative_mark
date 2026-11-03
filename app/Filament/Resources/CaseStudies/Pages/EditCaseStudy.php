<?php

namespace App\Filament\Resources\CaseStudies\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCaseStudy extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = CaseStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [TranslateMissingLanguagesAction::make(), DeleteAction::make()];
    }
}
