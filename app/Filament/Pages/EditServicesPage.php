<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The Business solutions overview page (/services) in the page editor. */
class EditServicesPage extends EditPageSections
{
    protected static string $pageKey = 'services';

    protected static ?string $slug = 'pages/services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = -8;

    /** Under Pages; opening it shows every solution beneath it (EditServicePage::navigationItems). */
    public static function getNavigationLabel(): string
    {
        return __('Business solutions');
    }

    public function getTitle(): string
    {
        return __('Business solutions page');
    }
}
