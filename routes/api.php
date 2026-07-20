<?php

declare(strict_types=1);

use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\OrganizerApiController;
use App\Http\Controllers\Api\V1\EventApiController as EventApiV1Controller;
use App\Http\Controllers\Public\EventWidgetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organizer.detect'])->prefix('v1')->group(function (): void {
    Route::get('/organizers/{organizer}', [OrganizerApiController::class, 'show'])->name('api.organizers.show');
    Route::get('/organizers/{organizer}/events', [EventApiController::class, 'index'])->name('api.events.index');

    // Rutas operacionales del Evento con Throttling
    Route::middleware('throttle:api')->prefix('events/{event}')->group(function (): void {
        Route::get('/attendees', [EventApiV1Controller::class, 'attendees'])->name('api.events.attendees');
        Route::post('/check-in', [EventApiV1Controller::class, 'checkIn'])->name('api.events.check-in');
        Route::post('/messages', [EventApiV1Controller::class, 'messages'])->name('api.events.messages');
    });
});

Route::middleware(['auth:sanctum', 'global.admin', 'role:super_admin|platform_admin', 'throttle:60,1'])->prefix('v1/admin')->name('api.admin.')->group(function (): void {
    Route::get('/users', [App\Http\Controllers\Api\V1\Admin\UserApiController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [App\Http\Controllers\Api\V1\Admin\UserApiController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/suspend', [App\Http\Controllers\Api\V1\Admin\UserApiController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/restore', [App\Http\Controllers\Api\V1\Admin\UserApiController::class, 'restore'])->name('users.restore');

    Route::get('/events', [App\Http\Controllers\Api\V1\Admin\EventApiController::class, 'index'])->name('events.index');
    Route::post('/events/{event}/suspend', [App\Http\Controllers\Api\V1\Admin\EventApiController::class, 'suspend'])->name('events.suspend');
    Route::post('/events/{event}/restore', [App\Http\Controllers\Api\V1\Admin\EventApiController::class, 'restore'])->name('events.restore');
});

Route::get('/widget/events', EventWidgetController::class)->name('api.widget.events');

Route::post('/v1/webhooks/stripe', App\Http\Controllers\Payments\StripeWebhookController::class)->name('webhooks.stripe');
