<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Every word of the consultation booking form and its confirmation page, per language. */
class EditBookingTexts extends EditFixedTexts
{
    protected const GROUP = 'ui';

    protected static ?string $slug = 'pages/booking-texts';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    protected static function labels(): array
    {
        return [
            'bookTitle' => [null, false],
            'bookIntro' => [null, true],
            'bookPoint1' => [null, false],
            'bookPoint2' => [null, false],
            'bookPoint3' => [null, false],
            'stepNeeds' => [null, false],
            'stepAbout' => [null, false],
            'stepTime' => [null, false],
            'stepReview' => [null, false],
            'qService' => [null, false],
            'notSure' => [null, false],
            'qRegion' => [null, false],
            'qStage' => [null, false],
            'stage_exploring' => [null, false],
            'stage_ready' => [null, false],
            'stage_operating' => [null, false],
            'fName' => [null, false],
            'fEmail' => [null, false],
            'fPhone' => [null, false],
            'fCompany' => [null, false],
            'fCountry' => [null, false],
            'otherCountry' => [null, false],
            'qMode' => [null, false],
            'mode_video' => [null, false],
            'mode_phone' => [null, false],
            'mode_office_riyadh' => [null, false],
            'mode_office_cairo' => [null, false],
            'qDate' => [null, false],
            'qSlot' => [null, false],
            'riyadhNote' => [null, true],
            'slotTaken' => [null, false],
            'fMessage' => [null, false],
            'consentText' => [null, true],
            'continue' => [null, false],
            'back' => [null, false],
            'confirmBooking' => [null, false],
            'sending' => [null, false],
            'edit' => [null, false],
            'errRequired' => [null, false],
            'errGeneric' => [null, false],
            'errEmail' => [null, false],
            'errPhone' => [null, true],
            'errSlotTaken' => [null, false],
            'errTooMany' => [null, false],
            'errExpired' => [null, true],
            'errConsent' => [null, false],
            'errNoHtml' => [null, false],
            'thanksTitle' => [null, false],
            'thanksBody' => [null, true],
            'yourReference' => [null, false],
            'copy' => [null, false],
            'copied' => [null, false],
            'checkSpam' => [null, true],
            'nextTitle' => [null, false],
            'next1' => [null, false],
            'next2' => [null, true],
            'next3' => [null, false],
            'backHome' => [null, false],
            'readGuide' => [null, false],
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Pages');
    }

    public static function getNavigationLabel(): string
    {
        return __('Booking form');
    }

    public function getTitle(): string
    {
        return __('Booking form');
    }

    protected function screenDescription(): string
    {
        return __('Every word on the booking form and its confirmation page.');
    }

    protected function previewPath(): string
    {
        return '/consultation';
    }
}
