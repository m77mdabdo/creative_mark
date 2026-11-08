<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->draftActions(), TranslateMissingLanguagesAction::make(), DeleteAction::make()];
    }
}
