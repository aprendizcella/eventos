<?php

declare(strict_types=1);

use App\Actions\Events\PauseEventAction;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('pauses a published event', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $action = resolve(PauseEventAction::class);
    $paused = $action($event);

    expect($paused->status)->toBe(EventStatus::Paused);
});

it('rejects pause from draft status', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);

    $action = resolve(PauseEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('rejects pause from cancelled status', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Cancelled]);

    $action = resolve(PauseEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('logs activity on pause', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $action = resolve(PauseEventAction::class);
    $action($event);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Event::class)
        ->where('subject_id', $event->event_id)
        ->where('description', 'paused')
        ->first();

    expect($activity)->not->toBeNull();
});
