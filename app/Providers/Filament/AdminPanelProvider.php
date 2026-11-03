<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Http\Middleware\SetAdminLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Staff dashboard (ADR-0002). Same session, guard and policies as the public site; access is decided by
 * User::canAccessPanel (active + admin.access). MFA is mandatory for publishing and export roles.
 */
class AdminPanelProvider extends PanelProvider
{
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
            ->darkMode(true, isForced: true)
            ->colors([
                'primary' => Color::hex('#EBBD6C'),
                'gray' => Color::Zinc,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
