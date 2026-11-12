<?php

use App\Http\ErrorPage;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ShowDraftsInPreview;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only APP_URL's host (and its subdomains) is answered; Laravel skips this check in local and testing.
        $middleware->trustHosts();
        $middleware->web(append: [SecurityHeaders::class, HandleInertiaRequests::class, ShowDraftsInPreview::class]);
        // The only sign-in is the dashboard's.
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // Public pages get a designed error page in the visitor's language; the dashboard keeps Filament's.
        $exceptions->respond(fn (Response $response, Throwable $e, Request $request) => ErrorPage::respond($response, $request));
    })->create();
