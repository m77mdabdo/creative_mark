<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The Investor guide in the page editor (Pages › Investor guide): chapters, checklist and sources. */
class EditInvestorGuidePage extends EditPageSections
{
    protected static string $pageKey = 'investor-guide';

    protected static ?string $slug = 'pages/investor-guide';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = -5;

    public static function getNavigationLabel(): string
    {
        return __('Investor guide');
    }

    public function getTitle(): string
    {
        return __('Investor guide');
    }
}
