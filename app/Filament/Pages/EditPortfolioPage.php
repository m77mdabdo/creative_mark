<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The Our portfolio page in the page editor (Pages › Our portfolio): client strip, years and achievements, sectors. */
class EditPortfolioPage extends EditPageSections
{
    protected static string $pageKey = 'portfolio';

    protected static ?string $slug = 'pages/portfolio';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = -7;

    public static function getNavigationLabel(): string
    {
        return __('Our portfolio');
    }

    public function getTitle(): string
    {
        return __('Our portfolio');
    }
}
