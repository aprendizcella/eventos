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
    $this->category = Category::factory()->create(['name' => 'Music', 'slug' => 'music']);
    $this->organizer = Organizer::factory()->create(['name' => 'Acme Events']);
    $this->venue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'New York',
        'name' => 'Madison Square Garden',
    ]);
});

it('renders the event detail page for a public published event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Awesome Concert',
        'description' => 'This is an amazing concert description.',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    Livewire::test('public.events.event-detail-public', ['event' => $event])
        ->assertSee('Awesome Concert')
        ->assertSee('This is an amazing concert description.');
});

it('returns 404 for private events on the detail page', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
        'title' => 'Private Event',
    ]);

    $this->get(route('public.events.detail', $event))
        ->assertNotFound();
});

it('shows a link to checkout on the detail page', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Concert with Tickets',
    ]);

    Livewire::test('public.events.event-detail-public', ['event' => $event])
        ->assertSee(route('checkout', $event));
});

it('shows organizer name on the detail page', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Concert',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    Livewire::test('public.events.event-detail-public', ['event' => $event])
        ->assertSee('Acme Events');
});

it('shows add to calendar link when event has a start date', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Concert',
        'starts_at' => '2026-08-15 20:00:00',
        'ends_at' => '2026-08-15 23:00:00',
    ]);

    Livewire::test('public.events.event-detail-public', ['event' => $event])
        ->assertSee('Google Calendar')
        ->assertSee('Apple Calendar');
});
