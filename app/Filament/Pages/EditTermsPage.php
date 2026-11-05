<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Terms of use in the page editor (Pages). */
class EditTermsPage extends EditPageSections
{
    protected static string $pageKey = 'terms';

    protected static ?string $slug = 'pages/terms';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Terms of use');
    }

    public function getTitle(): string
    {
        return __('Terms of use');
    }
}
