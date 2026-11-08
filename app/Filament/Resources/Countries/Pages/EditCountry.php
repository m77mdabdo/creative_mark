<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Countries\CountryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->draftActions(), TranslateMissingLanguagesAction::make(), DeleteAction::make()];
    }
}
