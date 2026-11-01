<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = FaqResource::class;
}
