<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Switches the signed-in staff member's dashboard language (Arabic or English) and remembers it on their account. */
class AdminLocaleController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->validate(['locale' => ['required', 'in:ar,en']])['locale'];
        $request->user()->forceFill(['admin_locale' => $locale])->save();

        return redirect()->back(fallback: '/'.trim(config('admin.path'), '/'));
    }
}
