<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

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
    $this->organizer = Organizer::factory()->create(['name' => 'Test Organizer']);
    $this->category = Category::factory()->create(['name' => 'Music', 'slug' => 'music']);
    $this->venue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'New York',
        'name' => 'Madison Square Garden',
    ]);
});

it('redirects numeric event URL to canonical slug URL with 301', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Awesome Concert',
        'slug' => 'awesome-concert',
    ]);

    $this->get(route('public.events.redirect', ['id' => $event->event_id]))
        ->assertRedirect(route('public.events.detail', $event->slug))
        ->assertStatus(301);
});

it('returns 404 for unknown numeric event', function (): void {
    $this->get(route('public.events.redirect', ['id' => 99999]))
        ->assertNotFound();
});

it('returns 404 for numeric private event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
        'title' => 'Private Event',
    ]);

    $this->get(route('public.events.redirect', ['id' => $event->event_id]))
        ->assertNotFound();
});

it('returns 404 for numeric unpublished event', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
        'title' => 'Draft Event',
    ]);

    $this->get(route('public.events.redirect', ['id' => $event->event_id]))
        ->assertNotFound();
});
