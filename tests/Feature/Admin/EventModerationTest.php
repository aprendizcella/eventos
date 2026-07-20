<?php

declare(strict_types=1);

use App\Actions\Admin\Events\RestoreEventAction;
use App\Actions\Admin\Events\SuspendEventAction;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

test('it suspends a published event, storing previous status and activity log', function () {
    $admin = User::factory()->create();
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $action = resolve(SuspendEventAction::class);
    $result = $action($event, 'Violation of terms', $admin);

    expect($result->status)->toBe(EventStatus::Suspended)
        ->and($result->previous_status)->toBe(EventStatus::Published->value)
        ->and($result->suspended_at)->not->toBeNull();

    $log = Activity::query()->where('description', 'suspended')->latest()->first();
    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($admin->id)
        ->and($log->properties['reason'])->toBe('Violation of terms');
});

test('it restores a suspended event to its previous status', function () {
    $admin = User::factory()->create();
    $event = Event::factory()->create([
        'status' => EventStatus::Suspended,
        'previous_status' => EventStatus::Published->value,
        'suspended_at' => now(),
    ]);

    $action = resolve(RestoreEventAction::class);
    $result = $action($event, $admin);

    expect($result->status)->toBe(EventStatus::Published)
        ->and($result->previous_status)->toBeNull()
        ->and($result->suspended_at)->toBeNull();
});

test('it requires a reason to suspend an event', function () {
    $admin = User::factory()->create();
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $action = resolve(SuspendEventAction::class);

    expect(fn () => $action($event, '', $admin))->toThrow(ValidationException::class);
});

test('it does not trigger automatic refunds or payout changes on suspension', function () {
    // Fake queue and HTTP to prove no side effects occur (e.g. Stripe refunds)
    Illuminate\Support\Facades\Queue::fake();
    Illuminate\Support\Facades\Http::fake();
    Illuminate\Support\Facades\Event::fake([
        App\Events\Payments\RefundRequested::class ?? stdClass::class, // if it exists
    ]);

    $admin = User::factory()->create();
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $action = resolve(SuspendEventAction::class);
    $action($event, 'Violation of terms', $admin);

    Illuminate\Support\Facades\Queue::assertNothingPushed();
    Illuminate\Support\Facades\Http::assertNothingSent();

    // Test passes if no side-effect exceptions were thrown and queues are empty
});
