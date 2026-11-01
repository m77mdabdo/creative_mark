<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Faqs\FaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaq extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [TranslateMissingLanguagesAction::make(), DeleteAction::make()];
    }
}
