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

it('returns 404 for private events on the detail page via slug', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
        'title' => 'Private Event',
        'slug' => 'private-event',
    ]);

    $this->get(route('public.events.detail', $event->slug))
        ->assertNotFound();
});

it('returns 404 for unpublished events on the detail page via slug', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
        'title' => 'Draft Event',
        'slug' => 'draft-event',
    ]);

    $this->get(route('public.events.detail', $event->slug))
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

it('renders SEO metadata title for a public event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'SEO Concert',
        'slug' => 'seo-concert',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $this->get(route('public.events.detail', $event->slug))
        ->assertOk()
        ->assertSee('<title>', false);
});

it('renders canonical URL in the page for a public event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Canonical Event',
        'slug' => 'canonical-event',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $this->get(route('public.events.detail', $event->slug))
        ->assertOk()
        ->assertSee('canonical', false)
        ->assertSee('canonical-event', false);
});

it('renders Open Graph metadata for a public event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'OG Event',
        'slug' => 'og-event',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $this->get(route('public.events.detail', $event->slug))
        ->assertOk()
        ->assertSee('og:title', false)
        ->assertSee('og:description', false)
        ->assertSee('og:url', false);
});

it('renders Twitter Card metadata for a public event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Twitter Event',
        'slug' => 'twitter-event',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $this->get(route('public.events.detail', $event->slug))
        ->assertOk()
        ->assertSee('twitter:card', false)
        ->assertSee('twitter:title', false)
        ->assertSee('twitter:description', false);
});
