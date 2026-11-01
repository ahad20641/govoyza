<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AdminIpAllowlist;
use App\Http\Middleware\SecurityHeaders;
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
 * Admin console on its own domain (admin.govoyza.com). Staff only (User::canAccessPanel),
 * 2FA required for everyone, optional IP allowlist. Every write here is audited
 * (App\Services\Audit\PanelWriteRecorder). What each staff role sees comes from
 * the Policies and config/permissions.php.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->domain(config('govoyza.domains.admin'))
            ->path(config('govoyza.admin.path'))
            ->brandName('GoVoyza Admin')
            ->login()
            ->profile(isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()->brandName('GoVoyza')->recoverable(),
            ], isRequired: true)
            ->colors([
                'primary' => Color::hex('#D4AF37'),
            ])
            // Sidebar order (decision 2026-11-01, "Skeleton first").
            ->navigationGroups(['Today', 'Catalogue', 'Orders', 'Users', 'Accounting', 'CMS', 'Settings'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            // Persistent: also runs on Livewire update requests, not just page loads.
            ->middleware([AdminIpAllowlist::class], isPersistent: true)
            ->middleware([
                SecurityHeaders::class.':panel',
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
            ]);
    }
}
