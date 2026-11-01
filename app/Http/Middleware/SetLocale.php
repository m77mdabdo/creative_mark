<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/** Applies the {locale} route prefix (ADR-0005) and remembers it for the "/" redirect. */
class SetLocale
{
    public const COOKIE = 'cm_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');
        abort_unless(Locales::isSupported($locale), 404);

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
        $request->route()->forgetParameter('locale');

        $response = $next($request);
        if ($request->cookie(self::COOKIE) !== $locale) {
            $response->headers->setCookie(Cookie::make(self::COOKIE, $locale, 60 * 24 * 365, httpOnly: true, sameSite: 'lax'));
        }

        return $response;
    }
}
