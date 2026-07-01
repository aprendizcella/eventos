<?php

declare(strict_types=1);

use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\OrganizerApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organizer.detect'])->prefix('v1')->group(function (): void {
    Route::get('/organizers/{organizer}', [OrganizerApiController::class, 'show'])->name('api.organizers.show');
    Route::get('/organizers/{organizer}/events', [EventApiController::class, 'index'])->name('api.events.index');
});

Route::post('/v1/webhooks/stripe', App\Http\Controllers\Payments\StripeWebhookController::class)->name('webhooks.stripe');
