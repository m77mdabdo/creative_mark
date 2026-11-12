<?php

namespace App\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\URL;

/**
 * In production every generated link (password reset and sign-in emails included) starts with APP_URL, never with
 * the Host header of the request that caused it, so a forged Host cannot point a link at another site.
 */
class CanonicalUrl
{
    public static function apply(Application $app): void
    {
        $root = rtrim((string) config('app.url'), '/');

        if (! $app->isProduction() || parse_url($root, PHP_URL_HOST) === null) {
            return;
        }

        URL::forceRootUrl($root);
        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
