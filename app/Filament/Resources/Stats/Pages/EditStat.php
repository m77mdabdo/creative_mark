<?php

namespace App\Filament\Resources\Stats\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Stats\StatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStat extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = StatResource::class;

    protected function getHeaderActions(): array
    {
        return [TranslateMissingLanguagesAction::make(), DeleteAction::make()];
    }
}
