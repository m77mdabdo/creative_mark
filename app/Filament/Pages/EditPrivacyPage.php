<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Privacy policy in the page editor (Pages). */
class EditPrivacyPage extends EditPageSections
{
    protected static string $pageKey = 'privacy';

    protected static ?string $slug = 'pages/privacy';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Privacy policy');
    }

    public function getTitle(): string
    {
        return __('Privacy policy');
    }
}
