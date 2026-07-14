<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->organizer = Organizer::factory()->create();
    $this->category = Category::factory()->create();
    $this->venue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'New York',
    ]);
});

it('includes title and description in searchable array', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Summer Music Festival',
        'description' => 'An amazing music festival in the park.',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $searchable = $event->toSearchableArray();

    expect($searchable['title'])->toBe('Summer Music Festival');
    expect($searchable['description'])->toBe('An amazing music festival in the park.');
});

it('includes structured filter attributes in searchable array', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $searchable = $event->toSearchableArray();

    expect($searchable)->toHaveKey('organizer_id');
    expect($searchable)->toHaveKey('category_id');
    expect($searchable)->toHaveKey('venue_city');
    expect($searchable)->toHaveKey('starts_at');
    expect($searchable['organizer_id'])->toBe($event->organizer_id);
    expect($searchable['category_id'])->toBe($this->category->category_id);
    expect($searchable['venue_city'])->toBe('New York');
    expect($searchable['starts_at'])->toBeNumeric();
});

it('returns true from shouldBeSearchable for published public events', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    expect($event->shouldBeSearchable())->toBeTrue();
});

it('returns false from shouldBeSearchable for non-published events', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
    ]);

    expect($event->shouldBeSearchable())->toBeFalse();
});

it('returns false from shouldBeSearchable for non-public events', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
    ]);

    expect($event->shouldBeSearchable())->toBeFalse();
});

it('returns false from shouldBeSearchable for draft and private events', function (): void {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Private,
    ]);

    expect($event->shouldBeSearchable())->toBeFalse();
});
