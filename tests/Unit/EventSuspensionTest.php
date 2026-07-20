<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('has a Suspended status', function () {
    expect(EventStatus::from('suspended'))->toBe(EventStatus::Suspended);
});

it('can store previous status and suspended_at', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);

    $event->previous_status = EventStatus::Draft->value;
    $event->status = EventStatus::Suspended;
    $event->suspended_at = now();
    $event->save();

    $event->refresh();
    expect($event->previous_status)->toBe(EventStatus::Draft->value);
    expect($event->suspended_at)->not->toBeNull();
});

it('excludes suspended events from searchable array', function () {
    $event = Event::factory()->create(['status' => EventStatus::Suspended]);
    expect($event->shouldBeSearchable())->toBeFalse();

    $activeEvent = Event::factory()->create([
        'status' => EventStatus::Published,
        'visibility' => App\Enums\EventVisibility::Public,
    ]);
    expect($activeEvent->shouldBeSearchable())->toBeTrue();
});
