<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use App\Services\Discovery\EventSearchService;
use Carbon\Carbon;
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

it('filters by date when searching with text query', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Summer Music Festival',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Fall Music Concert',
        'starts_at' => '2026-09-01 09:00:00',
    ]);

    $service = app(EventSearchService::class);
    // With SCOUT_DRIVER=database in tests, the DatabaseEngine fails on
    // virtual columns (venue_city), falling back to Eloquent LIKE which
    // correctly applies the date filter.
    $results = $service->search(
        query: 'Music',
        filters: ['date' => '2026-08-15'],
    );

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->title)->toBe('Summer Music Festival');
});

it('filters by date without text query', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Summer Music Festival',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Fall Music Concert',
        'starts_at' => '2026-09-01 09:00:00',
    ]);

    $service = app(EventSearchService::class);
    $results = $service->search(
        query: '',
        filters: ['date' => '2026-08-15'],
    );

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->title)->toBe('Summer Music Festival');
});

it('returns empty when date filter matches no events', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Summer Music Festival',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $service = app(EventSearchService::class);
    $results = $service->search(
        query: 'Music',
        filters: ['date' => '2027-01-01'],
    );

    expect($results->items())->toHaveCount(0);
});

it('combines date filter with category filter via text search', function (): void {
    $otherCategory = Category::factory()->create();

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Music Festival A',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $otherCategory->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Music Festival B',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $service = app(EventSearchService::class);
    $results = $service->search(
        query: 'Music',
        filters: [
            'date' => '2026-08-15',
            'category_id' => $this->category->category_id,
        ],
    );

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->title)->toBe('Music Festival A');
});

it('includes starts_at_date in searchable array for Scout filtering', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-15 12:00:00'));

    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Test Event',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $searchable = $event->toSearchableArray();

    expect($searchable)->toHaveKey('starts_at_date');
    expect($searchable['starts_at_date'])->toBe('2026-08-15');

    Carbon::setTestNow();
});
