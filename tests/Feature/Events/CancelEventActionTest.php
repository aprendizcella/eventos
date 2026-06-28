<?php

declare(strict_types=1);

use App\Actions\Events\CancelEventAction;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('cancels a draft event', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);

    $action = resolve(CancelEventAction::class);
    $cancelled = $action($event);

    expect($cancelled->status)->toBe(EventStatus::Cancelled);
});

it('cancels a configured event', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Configured]);

    $action = resolve(CancelEventAction::class);
    $cancelled = $action($event);

    expect($cancelled->status)->toBe(EventStatus::Cancelled);
});

it('cancels a published event', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $action = resolve(CancelEventAction::class);
    $cancelled = $action($event);

    expect($cancelled->status)->toBe(EventStatus::Cancelled);
});

it('cancels a paused event', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Paused]);

    $action = resolve(CancelEventAction::class);
    $cancelled = $action($event);

    expect($cancelled->status)->toBe(EventStatus::Cancelled);
});

it('rejects cancel from cancelled status', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Cancelled]);

    $action = resolve(CancelEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('rejects cancel from completed status', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Completed]);

    $action = resolve(CancelEventAction::class);

    expect(fn () => $action($event))->toThrow(ValidationException::class);
});

it('logs activity on cancel', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $action = resolve(CancelEventAction::class);
    $action($event);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Event::class)
        ->where('subject_id', $event->event_id)
        ->where('description', 'cancelled')
        ->first();

    expect($activity)->not->toBeNull();
});
