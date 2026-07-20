<?php

declare(strict_types=1);

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RequestPasswordResetController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Billing\DownloadInvoiceController;
use App\Http\Controllers\Organizers\EventController;
use App\Http\Controllers\Organizers\OrganizerController;
use App\Http\Controllers\Organizers\TeamController;
use App\Http\Controllers\Organizers\VenueController;
use App\Http\Controllers\Public\EventRedirectController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::mount();

Volt::route('/', 'public.events.event-list-public')->name('public.events.catalog');

// Numeric redirect route must come before slug route to avoid slug resolution of numeric IDs
Route::get('/events/{id}', EventRedirectController::class)
    ->whereNumber('id')
    ->name('public.events.redirect');

Volt::route('/events/{event:slug}', 'public.events.event-detail-public')->name('public.events.detail');

Route::get('/sitemap.xml', SitemapController::class)->name('public.sitemap');

Volt::route('/login', 'auth.login')->name('login');

Route::get('/health', Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::class)->name('health');
Volt::route('/register', 'auth.register')->name('register');
Volt::route('/forgot-password', 'auth.forgot-password')->name('forgot-password');
Volt::route('/reset-password/{token}', 'auth.reset-password')->name('password.reset');

Volt::route('/checkout/{event}', 'public.events.checkout')
    ->name('checkout');

Volt::route('/event/{event}/waitlist', 'public.events.join-waitlist')
    ->name('public.events.join-waitlist');

Volt::route('/checkout/{event}/order/{ticketOrder}/confirmation', 'public.events.order-confirmation')
    ->middleware('signed')
    ->name('checkout.confirmation');

Volt::route('/my-orders', 'public.orders.my-orders')
    ->name('public.orders.my-orders');

Volt::route('/my-orders/view', 'public.orders.view-orders')
    ->name('public.orders.view');

Route::post('/login', LoginController::class)
    ->middleware('throttle:login')
    ->name('login.post');

Route::post('/register', RegisterController::class)
    ->name('register.post');

Route::post('/logout', LogoutController::class)
    ->name('logout');

Route::post('/forgot-password', RequestPasswordResetController::class)
    ->middleware('throttle:password-reset-request')
    ->name('forgot-password.post');

Route::post('/reset-password', ResetPasswordController::class)
    ->name('password.reset.post');

Route::middleware(['auth'])->group(function () {
    Volt::route('/email/verify', 'auth.verify')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware(['verified'])->group(function () {
        Volt::route('/dashboard', 'dashboard')->name('dashboard');

        Route::prefix('admin')->name('admin.')->middleware(['role:super_admin|platform_admin'])->group(function () {
            Volt::route('/', 'admin.dashboard')->name('dashboard');
            Volt::route('/users', 'admin.users')->name('users');
            Volt::route('/events', 'admin.events')->name('events');
            Volt::route('/settings', 'admin.settings')->name('settings');
            Volt::route('/reports', 'admin.reports.platform-hub')->name('reports.index');
        });

        Route::prefix('account')->name('account.')->group(function () {
            Route::get('/profile', [AccountController::class, 'editProfile'])->name('profile.edit');
            Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
            Route::get('/password', [AccountController::class, 'editPassword'])->name('password.edit');
            Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');
        });

        Route::prefix('organizers')->name('organizers.')->group(function () {
            Route::get('/', [OrganizerController::class, 'index'])->name('index');
            Route::get('/create', [OrganizerController::class, 'create'])->name('create');
            Route::post('/', [OrganizerController::class, 'store'])->name('store');
            Route::get('/{organizer}', [OrganizerController::class, 'show'])->name('show');
            Route::get('/{organizer}/dashboard', [OrganizerController::class, 'dashboard'])->name('dashboard')->middleware('organizer.detect');
            Route::get('/{organizer}/settings', [OrganizerController::class, 'settings'])->name('settings')->middleware('organizer.detect');
            Route::get('/{organizer}/edit', [OrganizerController::class, 'edit'])->name('edit');
            Route::put('/{organizer}', [OrganizerController::class, 'update'])->name('update');
            Route::delete('/{organizer}', [OrganizerController::class, 'destroy'])->name('destroy');

            Route::prefix('{organizer}/team')->name('team.')->middleware('organizer.detect')->group(function () {
                Route::get('/', [TeamController::class, 'index'])->name('index');
                Route::post('/', [TeamController::class, 'store'])->name('store');
                Route::put('/{user}', [TeamController::class, 'update'])->name('update');
                Route::delete('/{user}', [TeamController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('{organizer}/events')->name('events.')->middleware('organizer.detect')->group(function () {
                Route::get('/', [EventController::class, 'index'])->name('index');
                Route::get('/create', [EventController::class, 'create'])->name('create');
                Route::post('/', [EventController::class, 'store'])->name('store');
                Route::get('/{event}', [EventController::class, 'show'])->name('show');
                Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
                Route::put('/{event}', [EventController::class, 'update'])->name('update');
                Route::post('/{event}/publish', [EventController::class, 'publish'])->name('publish');
                Route::post('/{event}/pause', [EventController::class, 'pause'])->name('pause');
                Route::post('/{event}/cancel', [EventController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('{organizer}/venues')->name('venues.')->middleware('organizer.detect')->group(function () {
                Route::get('/', [VenueController::class, 'index'])->name('index');
                Route::get('/create', [VenueController::class, 'create'])->name('create');
                Route::post('/', [VenueController::class, 'store'])->name('store');
                Route::get('/{venue}/edit', [VenueController::class, 'edit'])->name('edit');
                Route::put('/{venue}', [VenueController::class, 'update'])->name('update');
            });

            Route::prefix('{organizer}/reports')->name('reports.')->middleware('organizer.detect')->group(function () {
                Route::get('/', [OrganizerController::class, 'reportsIndex'])->name('index');
                Route::get('/billing', [OrganizerController::class, 'reportsBilling'])->name('billing');
                Route::get('/payouts', [OrganizerController::class, 'reportsPayouts'])->name('payouts');
            });

            Route::prefix('{organizer}/invoices')->name('invoices.')->middleware('organizer.detect')->group(function () {
                Route::get('/{invoice}/download', DownloadInvoiceController::class)->name('download');
            });
        });
    });
});
