<?php

namespace App\Filament\Resources\NewsArticles\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\NewsArticles\NewsArticleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNewsArticle extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = NewsArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label(__('Preview'))->icon('heroicon-o-eye')->color('gray')
                ->url(fn () => route('admin.news.preview', ['slug' => $this->getRecord()->slug, 'locale' => app()->getLocale()]), shouldOpenInNewTab: true),
            TranslateMissingLanguagesAction::make(),
            DeleteAction::make(),
        ];
    }
}
