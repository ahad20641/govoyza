<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Agent;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Public;
use Illuminate\Support\Facades\Route;

/*
| Routes for govoyza.com: public site, customer portal (/account) and agent portal (/agent).
| The partner and admin domains are Filament panels (App\Providers\Filament\*PanelProvider).
| Don't add routes for those hosts here: a same-host route silently replaces the panel's.
*/

Route::domain(config('govoyza.domains.main'))->group(function () {

    // ==================== Public site (tasks 1.13–1.16) ====================
    // Shows Products only. Nothing here may name a partner or a cost (CLAUDE.md rule 1).

    Route::get('/', [Public\HomeController::class, 'show'])->name('home');

    Route::get('/visa', [Public\VisaController::class, 'index'])->name('visa.index');
    Route::get('/visa/{country}', [Public\VisaController::class, 'country'])->name('visa.country');
    Route::get('/visa/{country}/{visaType}', [Public\VisaController::class, 'visaType'])->name('visa.type');
    Route::get('/services/{product:slug}', [Public\VisaController::class, 'service'])->name('visa.service');

    Route::get('/track', [Public\TrackController::class, 'show'])->name('track');
    Route::post('/track', [Public\TrackController::class, 'lookup'])->middleware('throttle:10,1')->name('track.lookup');

    foreach (Public\PageController::PAGES as $slug => $page) {
        Route::get("/{$slug}", [Public\PageController::class, 'show'])->defaults('slug', $slug)->name("pages.{$slug}");
    }

    // ==================== Authentication (guests) ====================

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth');

        Route::get('/register', [RegisterController::class, 'choose'])->name('register');
        Route::get('/register/client', [RegisterController::class, 'createClient'])->name('register.client');
        Route::post('/register/client', [RegisterController::class, 'storeClient'])->middleware('throttle:registration');
        Route::get('/register/agency', [RegisterController::class, 'createAgency'])->name('register.agency');
        Route::post('/register/agency', [RegisterController::class, 'storeAgency'])->middleware('throttle:registration');

        Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
    });

    // ==================== Signed-in, any portal ====================

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
        Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
            ->middleware('throttle:otp-verify')->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:otp-send')->name('verification.send');

        Route::get('/account/pending', [DashboardController::class, 'pending'])->middleware('verified')->name('account.pending');

        Route::middleware('verified')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

            // ==================== Customer portal /account (task 1.22) ====================

            Route::prefix('account')->name('account.')->middleware('type:customer')->group(function () {
                Route::get('/', [Account\DashboardController::class, 'show'])->name('dashboard');
                Route::get('/applications', [Account\ApplicationController::class, 'index'])->name('applications.index');
                Route::get('/applications/{number}', [Account\ApplicationController::class, 'show'])->name('applications.show');
                Route::get('/travellers', [Account\TravellerController::class, 'index'])->name('travellers.index');
                Route::get('/payments', [Account\PaymentController::class, 'index'])->name('payments.index');
                Route::get('/support', [Account\SupportController::class, 'index'])->name('support.index');
                Route::get('/profile', [Account\ProfileController::class, 'show'])->name('profile.show');
            });

            // ==================== Agent portal /agent (Phase 2; shells in S1) ====================

            Route::prefix('agent')->name('agent.')->middleware('type:agent_user')->group(function () {
                Route::get('/', [Agent\DashboardController::class, 'show'])->name('dashboard');
                Route::get('/prices', [Agent\PriceListController::class, 'index'])->name('prices.index');
                Route::get('/applications/new', [Agent\ApplicationController::class, 'create'])->name('applications.create');
                Route::get('/applications/bulk', [Agent\ApplicationController::class, 'bulk'])->name('applications.bulk');
                Route::get('/wallet', [Agent\WalletController::class, 'show'])->name('wallet.show');
                Route::get('/users', [Agent\UserController::class, 'index'])->name('users.index');
                Route::get('/reports', [Agent\ReportController::class, 'index'])->name('reports.index');
            });
        });
    });
});
