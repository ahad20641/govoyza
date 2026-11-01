<?php

namespace App\Providers\Filament;

use App\Http\Controllers\Partner\InvitationController;
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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Partner (supplier) portal on partner.govoyza.com: noindex, never linked from
 * the public site, join by invite link only, 2FA required. Partner users only
 * (User::canAccessPanel). Every write here is audited as `partner.*`.
 */
class PartnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('partner')
            ->domain(config('govoyza.domains.partner'))
            ->path(config('govoyza.partner.path'))
            ->brandName('GoVoyza Partner')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()->brandName('GoVoyza Partner')->recoverable(),
            ], isRequired: true)
            ->colors([
                'primary' => Color::hex('#D4AF37'),
            ])
            ->navigationGroups(['My services', 'Jobs', 'Money'])
            ->discoverResources(in: app_path('Filament/Partner/Resources'), for: 'App\Filament\Partner\Resources')
            ->discoverPages(in: app_path('Filament/Partner/Pages'), for: 'App\Filament\Partner\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Partner/Widgets'), for: 'App\Filament\Partner\Widgets')
            ->routes(function () {
                Route::get('/invite/{token}', [InvitationController::class, 'show'])
                    ->middleware('throttle:30,1')->name('invitation');
                Route::post('/invite/{token}', [InvitationController::class, 'accept'])
                    ->middleware('throttle:registration')->name('invitation.accept');
            })
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
