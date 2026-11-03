<?php

namespace App\Filament\Resources\CaseStudies\Pages;

use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCaseStudy extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = CaseStudyResource::class;
}
