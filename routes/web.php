<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::domain(config('govoyza.domains.main'))->group(function () {
    // Public site is built in Phase 2.
    Route::view('/', 'home')->name('home');

    /*
    | Guest-only authentication routes
    */
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

    /*
    | Authenticated routes
    */
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

            Route::prefix('client')->name('client.')->middleware('type:customer')->group(function () {
                Route::get('/', [DashboardController::class, 'client'])->name('dashboard');
            });

            Route::prefix('agency')->name('agency.')->middleware('type:agent_user')->group(function () {
                Route::get('/', [DashboardController::class, 'agency'])->name('dashboard');
            });

            Route::prefix('vendor')->name('vendor.')->middleware('type:partner_user')->group(function () {
                Route::get('/', [DashboardController::class, 'vendor'])->name('dashboard');
            });

            Route::prefix('admin')->name('admin.')->middleware(['type:admin', 'can:admin.access'])->group(function () {
                Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
            });
        });
    });
});

Route::domain(config('govoyza.domains.partner'))->group(function () {
    Route::view('/', 'home')->name('partner.home');
});

Route::domain(config('govoyza.domains.admin'))->group(function () {
    Route::view('/', 'home')->name('admin.home');
});
