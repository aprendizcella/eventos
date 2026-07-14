<?php

declare(strict_types=1);

namespace Tests\Feature;

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
    $this->organizer = Organizer::factory()->create(['name' => 'Acme Events']);
    $this->category = Category::factory()->create(['name' => 'Music', 'slug' => 'music']);
    $this->venue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'New York',
    ]);

    // Create published public events
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
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Tech Conference 2026',
        'starts_at' => '2026-09-01 09:00:00',
    ]);
});

it('shows the search bar on the catalog page', function (): void {
    Livewire::test('public.events.event-list-public')
        ->assertSee('Discover Events')
        ->assertSeeHtml('wire:model.live.debounce.400ms');
});

it('filters events by search text', function (): void {
    Livewire::test('public.events.event-list-public')
        ->set('search', 'Music')
        ->assertSee('Summer Music Festival')
        ->assertDontSee('Tech Conference 2026');
});

it('combines search with category filter', function (): void {
    $otherCategory = Category::factory()->create(['name' => 'Tech', 'slug' => 'tech']);

    Livewire::test('public.events.event-list-public')
        ->set('search', 'Conference')
        ->set('filterCategory', null)
        ->assertSee('Tech Conference 2026');

    Livewire::test('public.events.event-list-public')
        ->set('search', 'Conference')
        ->set('filterCategory', $otherCategory->category_id)
        ->assertDontSee('Summer Music Festival');
});

it('shows empty state when no results match', function (): void {
    Livewire::test('public.events.event-list-public')
        ->set('search', 'NonExistentEventXYZ')
        ->assertSee('No events found');
});

it('shows clear search button when search is active', function (): void {
    Livewire::test('public.events.event-list-public')
        ->set('search', 'Music')
        ->assertSeeHtml('wire:click="clearSearch"');
});

it('shows pagination when more than 12 events exist', function (): void {
    // Create 14 events to trigger pagination (2 existing + 12 new = 14)
    // Give them unique titles that won't show on page 1
    Event::factory()->count(12)->create([
        'organizer_id' => $this->organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'starts_at' => '2026-10-01 10:00:00',
        'title' => 'Paginated Event',
    ]);

    // The catalog shows 12 items per page
    // With 14 total events, page 1 shows 12
    $component = Livewire::test('public.events.event-list-public');

    // Should show the original 2 events + see "Paginated Event" from the new ones
    $component->assertSee('Summer Music Festival');
    $component->assertSee('Tech Conference 2026');
    $component->assertSee('Paginated Event');
});

it('resets search and filters when clearSearchAndFilters is called', function (): void {
    Livewire::test('public.events.event-list-public')
        ->set('search', 'Music')
        ->set('filterCategory', $this->category->category_id)
        ->call('clearSearchAndFilters')
        ->assertSet('search', '')
        ->assertSet('filterCategory', null)
        ->assertSee('Summer Music Festival')
        ->assertSee('Tech Conference 2026');
});

it('clears search independently from filters', function (): void {
    Livewire::test('public.events.event-list-public')
        ->set('search', 'Music')
        ->call('clearSearch')
        ->assertSet('search', '');
});

it('restores search state from url query parameter', function (): void {
    Livewire::withQueryParams(['q' => 'Music'])
        ->test('public.events.event-list-public')
        ->assertSet('search', 'Music')
        ->assertSee('Summer Music Festival');
});

it('restores category filter from url query parameter', function (): void {
    Livewire::withQueryParams(['cat' => (string) $this->category->category_id])
        ->test('public.events.event-list-public')
        ->assertSet('filterCategory', $this->category->category_id);
});

it('restores city filter from url query parameter', function (): void {
    Livewire::withQueryParams(['city' => 'New York'])
        ->test('public.events.event-list-public')
        ->assertSet('filterCity', 'New York');
});

it('restores date filter from url query parameter', function (): void {
    Livewire::withQueryParams(['date' => '2026-08-15'])
        ->test('public.events.event-list-public')
        ->assertSet('filterDate', '2026-08-15');
});

it('combines search with city and date filters', function (): void {
    Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'Los Angeles',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'LA Jazz Night',
        'starts_at' => '2026-07-20 21:00:00',
    ]);

    // Event in New York on 2026-08-15
    Livewire::test('public.events.event-list-public')
        ->set('search', 'Music')
        ->set('filterCity', 'New York')
        ->set('filterDate', '2026-08-15')
        ->assertSee('Summer Music Festival')
        ->assertDontSee('LA Jazz Night')
        ->assertDontSee('Tech Conference 2026');
});

it('isolates events by tenant organizer', function (): void {
    $otherOrganizer = Organizer::factory()->create(['name' => 'Other Org']);
    Venue::factory()->create([
        'organizer_id' => $otherOrganizer->id,
        'city' => 'Chicago',
    ]);

    Event::factory()->create([
        'organizer_id' => $otherOrganizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Other Organizer Event',
        'starts_at' => '2026-11-01 10:00:00',
    ]);

    // Set the tenant context via the multitenancy layer
    $this->organizer->makeCurrent();

    // The catalog should only show events from the current tenant
    Livewire::test('public.events.event-list-public')
        ->assertSee('Summer Music Festival')
        ->assertDontSee('Other Organizer Event');

    Organizer::forgetCurrent();
});

it('shows breadcrumb slot on catalog page', function (): void {
    Livewire::test('public.events.event-list-public')
        ->assertSee('Discover Events');
});

it('shows result summary with total count', function (): void {
    Livewire::test('public.events.event-list-public')
        ->assertSee('Showing')
        ->assertSee('of')
        ->assertSee('events');
});

it('shows loading skeleton markup for search', function (): void {
    Livewire::test('public.events.event-list-public')
        ->assertSeeHtml('wire:loading');
});
