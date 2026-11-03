<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The home page in the page editor (Content › Home page). */
class EditHomePage extends EditPageSections
{
    protected static string $pageKey = 'home';

    protected static ?string $slug = 'pages/home';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?int $navigationSort = -10;

    public static function getNavigationLabel(): string
    {
        return __('Home page');
    }

    public function getTitle(): string
    {
        return __('Home page');
    }
}
