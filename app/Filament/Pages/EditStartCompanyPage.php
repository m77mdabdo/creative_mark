<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Start your company in the page editor (Pages). */
class EditStartCompanyPage extends EditPageSections
{
    protected static string $pageKey = 'start-your-company';

    protected static ?string $slug = 'pages/start-your-company';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?int $navigationSort = -2;

    public static function getNavigationLabel(): string
    {
        return __('Start your company');
    }

    public function getTitle(): string
    {
        return __('Start your company');
    }
}
