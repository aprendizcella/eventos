<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RequestPasswordResetController;
use App\Http\Controllers\Auth\ResetPasswordController;
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
});
