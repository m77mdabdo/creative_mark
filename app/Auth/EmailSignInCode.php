<?php

namespace App\Auth;

use App\Notifications\SignInCode;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;

/**
 * Every sign-in to the dashboard is finished with a one-time code emailed to the person signing in. It is always on
 * for every account, so the profile page shows it without buttons to switch it off.
 */
final class EmailSignInCode extends EmailAuthentication
{
    public static function make(): static
    {
        return parent::make()->codeNotification(SignInCode::class)->codeExpiryMinutes(10);
    }

    public function getActions(): array
    {
        return [];
    }
}
