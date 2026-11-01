<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "/" only redirects: saved preference, then Accept-Language, then Arabic (ADR-0005). */
class LocaleRedirectController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $saved = $request->cookie(SetLocale::COOKIE);
        $locale = Locales::isSupported($saved) ? $saved
            : (Locales::fromAcceptLanguage($request->header('Accept-Language')) ?? Locales::DEFAULT);

        return redirect('/'.$locale, 302)->header('Vary', 'Accept-Language, Cookie');
    }
}
