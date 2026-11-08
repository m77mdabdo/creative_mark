<?php

namespace App\Filament\Resources\Regions\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Regions\RegionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRegion extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = RegionResource::class;

    protected function formRelations(): array
    {
        return ['services'];
    }

    protected function getHeaderActions(): array
    {
        return [...$this->draftActions(), TranslateMissingLanguagesAction::make(), DeleteAction::make()];
    }
}
