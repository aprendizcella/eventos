<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->organizer = Organizer::factory()->create();
});

it('makes published public event searchable after commit', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Searchable Event',
    ]);

    expect($event->shouldBeSearchable())->toBeTrue();
    expect($event->toSearchableArray())->toHaveKey('title');
    expect($event->toSearchableArray()['title'])->toBe('Searchable Event');
});

it('does not index non-published event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
    ]);

    expect($event->shouldBeSearchable())->toBeFalse();
});

it('does not index non-public event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
    ]);

    expect($event->shouldBeSearchable())->toBeFalse();
});

it('marks event as unsearchable when unpublished', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    expect($event->shouldBeSearchable())->toBeTrue();

    $event->update(['status' => EventStatus::Draft]);

    $event->refresh();

    expect($event->shouldBeSearchable())->toBeFalse();
});

it('marks event as unsearchable when visibility changed to private', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    expect($event->shouldBeSearchable())->toBeTrue();

    $event->update(['visibility' => EventVisibility::Private]);

    $event->refresh();

    expect($event->shouldBeSearchable())->toBeFalse();
});

it('marks soft-deleted event as not searchable', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    expect($event->shouldBeSearchable())->toBeTrue();

    $event->delete();

    $event->refresh();

    expect($event->shouldBeSearchable())->toBeFalse();
});

it('does not include trashed events in searchable check', function (): void {
    // Create a scenario: event is published and public but soft-deleted
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    // Confirm searchable before delete
    expect($event->shouldBeSearchable())->toBeTrue();

    // Soft delete
    $event->deleteOrFail();
    $event->refresh();

    // Must not be searchable after soft delete
    expect($event->trashed())->toBeTrue();
    expect($event->shouldBeSearchable())->toBeFalse();
});
