<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** The wording of the page visitors see when a link is broken or something goes wrong. */
class EditErrorTexts extends EditFixedTexts
{
    protected const GROUP = 'ui';

    protected static ?string $slug = 'pages/error-texts';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 4;

    protected static function labels(): array
    {
        return [
            'notFoundTitle' => [null, false], 'notFoundText' => [null, true],
            'serverErrorTitle' => [null, false], 'serverErrorText' => [null, true],
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Pages');
    }

    public static function getNavigationLabel(): string
    {
        return __('Error page');
    }

    public function getTitle(): string
    {
        return __('Error page');
    }

    public function getSubheading(): string
    {
        return __('Shown when a link is broken or something goes wrong. Changes appear on the website when you save.');
    }
}
