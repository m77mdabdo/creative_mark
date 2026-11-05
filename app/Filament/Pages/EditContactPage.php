<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Contact us in the page editor (Pages). */
class EditContactPage extends EditPageSections
{
    protected static string $pageKey = 'contact';

    protected static ?string $slug = 'pages/contact';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('Contact us');
    }

    public function getTitle(): string
    {
        return __('Contact us');
    }
}
