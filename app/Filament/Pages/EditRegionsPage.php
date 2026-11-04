<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The Regions overview page (/regions) in the page editor. */
class EditRegionsPage extends EditPageSections
{
    protected static string $pageKey = 'regions';

    protected static ?string $slug = 'pages/regions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = -6;

    /** Under Pages; opening it shows every region beneath it (EditRegionPage::navigationItems). */
    public static function getNavigationLabel(): string
    {
        return __('Saudi regions');
    }

    public function getTitle(): string
    {
        return __('Saudi regions');
    }
}
