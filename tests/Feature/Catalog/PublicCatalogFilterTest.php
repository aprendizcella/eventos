<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->musicCategory = Category::factory()->create(['name' => 'Music', 'slug' => 'music']);
    $this->techCategory = Category::factory()->create(['name' => 'Tech', 'slug' => 'tech']);
    $this->organizer = Organizer::factory()->create();
});

it('filters events by category', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->musicCategory->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Music Concert',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->techCategory->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Tech Conference',
    ]);

    Livewire::test('public.events.event-list-public')
        ->set('filterCategory', $this->musicCategory->category_id)
        ->assertSee('Music Concert')
        ->assertDontSee('Tech Conference');
});

it('filters events by city', function (): void {
    $newYorkVenue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'New York',
    ]);

    $losAngelesVenue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'Los Angeles',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->musicCategory->category_id,
        'venue_id' => $newYorkVenue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'New York Event',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->musicCategory->category_id,
        'venue_id' => $losAngelesVenue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'LA Event',
    ]);

    Livewire::test('public.events.event-list-public')
        ->set('filterCity', 'New York')
        ->assertSee('New York Event')
        ->assertDontSee('LA Event');
});

it('filters events by date', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->musicCategory->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'starts_at' => '2026-08-15 10:00:00',
        'title' => 'August Event',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->musicCategory->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'starts_at' => '2026-09-20 14:00:00',
        'title' => 'September Event',
    ]);

    Livewire::test('public.events.event-list-public')
        ->set('filterDate', '2026-08-15')
        ->assertSee('August Event')
        ->assertDontSee('September Event');
});

it('shows empty state when no events match filters', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->musicCategory->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Music Concert',
    ]);

    // Filter to a category with no events
    $emptyCategory = Category::factory()->create(['name' => 'Sports', 'slug' => 'sports']);

    Livewire::test('public.events.event-list-public')
        ->set('filterCategory', $emptyCategory->category_id)
        ->assertSee(__('No events found'))
        ->assertDontSee('Music Concert');
});
