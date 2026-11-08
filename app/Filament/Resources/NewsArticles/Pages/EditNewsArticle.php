<?php

namespace App\Filament\Resources\NewsArticles\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\NewsArticles\NewsArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNewsArticle extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = NewsArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->draftActions(),
            TranslateMissingLanguagesAction::make(),
            DeleteAction::make(),
        ];
    }
}
