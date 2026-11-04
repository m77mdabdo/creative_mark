<?php

namespace App\Http\Middleware;

use App\Filament\Pages\EditNewsPage;
use App\Filament\Pages\EditRegionPage;
use App\Filament\Pages\EditServicePage;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The dashboard speaks the staff member's own language (LR-08), independent of the public site's URL locale. */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->admin_locale;
        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');
        // Menu groups in a fixed order, labelled in the language just chosen.
        $panel = Filament::getCurrentPanel();
        $panel?->navigationGroups([__('Pages'), __('Clients'), __('Content'), __('Settings')]);
        // Every business solution and region has its own entry under Pages.
        if ($request->user()) {
            $panel?->navigationItems([...EditServicePage::navigationItems(), ...EditRegionPage::navigationItems(), ...EditNewsPage::navigationItems()]);
        }

        return $next($request);
    }
}
