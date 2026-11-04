<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The Our edge page in the page editor (Pages › Our edge). */
class EditOurEdgePage extends EditPageSections
{
    protected static string $pageKey = 'our-edge';

    protected static ?string $slug = 'pages/our-edge';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = -7;

    public static function getNavigationLabel(): string
    {
        return __('Our edge');
    }

    public function getTitle(): string
    {
        return __('Our edge');
    }
}
