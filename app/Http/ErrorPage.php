<?php

namespace App\Http;

use App\Content\MediaLibrary;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Locales;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Renders 404, 403, 500 and 503 responses of the public site as a branded Inertia page in the visitor's language
 * (taken from the first URL segment, then the browser). Other responses, JSON and the dashboard are left as they are;
 * in local debug mode server errors keep Laravel's diagnostic page.
 */
final class ErrorPage
{
    private const STATUSES = [403, 404, 500, 503];

    public static function respond(Response $response, Request $request): Response
    {
        $status = $response->getStatusCode();
        if (! in_array($status, self::STATUSES, true) || $request->expectsJson()
            || $request->is(trim(config('admin.path'), '/'), trim(config('admin.path'), '/').'/*', 'livewire*', 'build/*', 'media/*')
            || ($status >= 500 && config('app.debug'))) {
            return $response;
        }

        try {
            $first = explode('/', trim($request->path(), '/'))[0] ?? '';
            app()->setLocale(in_array($first, Locales::codes(), true) ? $first : (Locales::fromAcceptLanguage($request->header('Accept-Language')) ?? Locales::FALLBACK));
            Inertia::share(app(HandleInertiaRequests::class)->share($request));

            return Inertia::render('ErrorPage', [
                'pageKey' => 'error',
                'status' => $status,
                'media' => MediaLibrary::responsive(['cm-desert-road'], app()->getLocale()),
            ])->toResponse($request)->setStatusCode($status);
        } catch (Throwable) {
            return $response; // never let the error page itself fail
        }
    }
}
