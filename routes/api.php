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

Route::get('/widget/events', EventWidgetController::class)->name('api.widget.events');

Route::post('/v1/webhooks/stripe', App\Http\Controllers\Payments\StripeWebhookController::class)->name('webhooks.stripe');
