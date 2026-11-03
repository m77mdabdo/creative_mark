<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The About page in the page editor (Pages › About us). */
class EditAboutPage extends EditPageSections
{
    protected static string $pageKey = 'about';

    protected static ?string $slug = 'pages/about';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = -9;

    public static function getNavigationLabel(): string
    {
        return __('About us');
    }

    public function getTitle(): string
    {
        return __('About us');
    }
}
