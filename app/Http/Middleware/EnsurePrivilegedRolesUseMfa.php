<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Filament decides "MFA required" once per panel at boot. Our rule is per role (Phase E/F §8 rule 4):
 * Super Admin, Content Manager and Sales Manager must enrol; other roles may enrol from their profile.
 */
class EnsurePrivilegedRolesUseMfa extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Filament::auth()->user()?->requiresMfa()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
