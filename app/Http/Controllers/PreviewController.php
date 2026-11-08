<?php

namespace App\Http\Controllers;

use App\Drafts\PreviewMode;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Turns preview mode on (staff only) and opens a page of the site, or turns it off again. */
class PreviewController
{
    public function __construct(private readonly PreviewMode $preview) {}

    public function start(Request $request): RedirectResponse
    {
        abort_unless(PreviewMode::allowed($request->user()), 403);
        $this->preview->start($request);

        return redirect($this->sitePath($request));
    }

    public function stop(Request $request): RedirectResponse
    {
        $this->preview->stop($request);

        return redirect($this->sitePath($request));
    }

    /** Only a path on this site, with a language prefix: never an outside address. */
    private function sitePath(Request $request): string
    {
        $path = '/'.ltrim((string) $request->query('path', ''), '/');
        $locale = (string) $request->query('locale', Locales::FALLBACK);
        $locale = in_array($locale, Locales::codes(), true) ? $locale : Locales::FALLBACK;
        if (! preg_match('#^/[a-z0-9\-/]*$#', $path)) {
            $path = '/';
        }
        $first = explode('/', trim($path, '/'))[0];

        return in_array($first, Locales::codes(), true) ? $path : rtrim('/'.$locale.$path, '/');
    }
}
