<?php

namespace App\Http\Middleware;

use App\Drafts\Drafts;
use App\Drafts\PreviewMode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * In preview mode a public page is rendered with every unpublished change applied inside a database transaction
 * that is always rolled back, so the same code renders previews and the live site and nothing leaks to visitors.
 */
class ShowDraftsInPreview
{
    public function __construct(private readonly PreviewMode $preview, private readonly Drafts $drafts) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() || ! $this->preview->active($request)) {
            return $next($request);
        }

        DB::beginTransaction();
        try {
            $this->drafts->applyAll();
            $response = $next($request);
        } finally {
            DB::rollBack();
        }
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
