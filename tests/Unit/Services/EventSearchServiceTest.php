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

// ─── From-date (inclusive lower bound) — same-day inclusion ───

it('from-date filter includes events on the same date', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Summer Music Festival',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $service = resolve(EventSearchService::class);
    $results = $service->search(
        query: '',
        filters: ['date' => '2026-08-15'],
    );

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->title)->toBe('Summer Music Festival');
});

// ─── From-date — later-date inclusion ───

it('from-date filter includes events after the selected date', function (): void {
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
        'title' => 'Fall Concert',
        'starts_at' => '2026-09-01 09:00:00',
    ]);

    $service = resolve(EventSearchService::class);
    $results = $service->search(
        query: '',
        filters: ['date' => '2026-08-15'],
    );

    expect($results->items())->toHaveCount(2);
    expect($results->items()[0]->title)->toBe('Summer Music Festival');
    expect($results->items()[1]->title)->toBe('Fall Concert');
});

// ─── From-date — earlier-date exclusion ───

it('from-date filter excludes events strictly before the selected date', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Early Bird Jazz',
        'starts_at' => '2026-07-20 21:00:00',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Summer Music Festival',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $service = resolve(EventSearchService::class);
    $results = $service->search(
        query: '',
        filters: ['date' => '2026-08-15'],
    );

    expect($results->items())->toHaveCount(1);
    expect($results->items()[0]->title)->toBe('Summer Music Festival');
});

// ─── From-date + text search combo ───

it('from-date filter combines with text search via fallback', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'July Music Jazz',
        'starts_at' => '2026-07-20 21:00:00',
    ]);

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

    $service = resolve(EventSearchService::class);
    // With SCOUT_DRIVER=database in tests, the DatabaseEngine fails on
    // virtual columns (venue_city), falling back to Eloquent LIKE which
    // correctly applies the from-date filter.
    $results = $service->search(
        query: 'Music',
        filters: ['date' => '2026-08-15'],
    );

    // "July Music Jazz" is before 2026-08-15 → excluded by from-date.
    // "Summer Music Festival" and "Fall Music Concert" both have "Music"
    // in the title AND are on/after the filter date → included.
    expect($results->items())->toHaveCount(2);
    expect($results->items()[0]->title)->toBe('Summer Music Festival');
    expect($results->items()[1]->title)->toBe('Fall Music Concert');
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

    $service = resolve(EventSearchService::class);
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

    $service = resolve(EventSearchService::class);
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
    \Illuminate\Support\Facades\Date::setTestNow(\Illuminate\Support\Facades\Date::parse('2026-08-15 12:00:00'));

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

    \Illuminate\Support\Facades\Date::setTestNow();
});
