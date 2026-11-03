<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Http\Middleware\SetAdminLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Component;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Staff dashboard (ADR-0002). Same session, guard and policies as the public site; access is decided by
 * User::canAccessPanel (active + admin.access). MFA is mandatory for publishing and export roles.
 */
class AdminPanelProvider extends PanelProvider
{
    /** Every label Filament derives from a field or column name goes through lang/ar.json (Arabic and English dashboard). */
    public function boot(): void
    {
        Component::configureUsing(fn (Component $c) => method_exists($c, 'translateLabel') ? $c->translateLabel() : null);
        Column::configureUsing(fn (Column $c) => $c->translateLabel());
        BaseFilter::configureUsing(fn (BaseFilter $f) => $f->translateLabel());
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('admin.path'))
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(EnsurePrivilegedRolesUseMfa::class)
            ->brandName('Creative Mark')
            ->brandLogo(asset('brand/wordmark-gold.png'))
            ->brandLogoHeight('2.4rem')
            ->favicon(asset('brand/wordmark-gold.png'))
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Light)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::hex('#EBBD6C'),
                'gray' => Color::Zinc,
            ])
            ->sidebarCollapsibleOnDesktop()
            // Language switch (Arabic / English) next to the user menu
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => Blade::render('filament.locale-switch'))
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetAdminLocale::class,
            ]);
    }
}
