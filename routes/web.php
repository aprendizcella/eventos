<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RequestPasswordResetController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Organizers\OrganizerController;
use App\Http\Controllers\Organizers\TeamController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::mount();

Route::get('/', fn () => view('welcome'));

Volt::route('/login', 'auth.login')->name('login');
Volt::route('/register', 'auth.register')->name('register');
Volt::route('/forgot-password', 'auth.forgot-password')->name('forgot-password');
Volt::route('/reset-password/{token}', 'auth.reset-password')->name('password.reset');

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
    Volt::route('/dashboard', 'dashboard')->name('dashboard');

    Route::prefix('organizers')->name('organizers.')->group(function () {
        Route::get('/', [OrganizerController::class, 'index'])->name('index');
        Route::get('/create', [OrganizerController::class, 'create'])->name('create');
        Route::post('/', [OrganizerController::class, 'store'])->name('store');
        Route::get('/{organizer}', [OrganizerController::class, 'show'])->name('show');
        Route::get('/{organizer}/edit', [OrganizerController::class, 'edit'])->name('edit');
        Route::put('/{organizer}', [OrganizerController::class, 'update'])->name('update');
        Route::delete('/{organizer}', [OrganizerController::class, 'destroy'])->name('destroy');

        Route::prefix('{organizer}/team')->name('team.')->middleware('organizer.detect')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::post('/', [TeamController::class, 'store'])->name('store');
            Route::put('/{user}', [TeamController::class, 'update'])->name('update');
            Route::delete('/{user}', [TeamController::class, 'destroy'])->name('destroy');
        });
    });
});
