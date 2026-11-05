<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Questions page in the page editor (Pages). */
class EditFaqPage extends EditPageSections
{
    protected static string $pageKey = 'faq';

    protected static ?string $slug = 'pages/faq';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return __('Questions page');
    }

    public function getTitle(): string
    {
        return __('Questions page');
    }
}
