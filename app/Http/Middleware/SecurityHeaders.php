<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser protections on every page of the site and the dashboard: no framing by other sites (clickjacking), no
 * content-type guessing, no full addresses sent to other sites, no camera, microphone or location access, and
 * HTTPS only once the site is served over HTTPS. In production a Content-Security-Policy is sent in report-only mode:
 * browsers log anything outside it without blocking, so it can be tightened and enforced once nothing is reported.
 */
class SecurityHeaders
{
    /** Everything the site and dashboard load is served from this origin (fonts and images included). */
    public const POLICY = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; "
        ."img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; "
        ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        if (app()->isProduction()) {
            $headers->set('Content-Security-Policy-Report-Only', self::POLICY);
        }
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
