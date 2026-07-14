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
    $this->organizer = Organizer::factory()->create(['name' => 'Acme Events', 'slug' => 'acme']);
    $this->category = Category::factory()->create(['name' => 'Music', 'slug' => 'music']);
    $this->venue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'New York',
        'name' => 'Madison Square Garden',
    ]);
});

it('returns widget JSON with correct structure for organizer', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Awesome Concert',
        'slug' => 'awesome-concert',
        'starts_at' => '2026-08-15 20:00:00',
    ]);

    $response = $this->getJson('/api/widget/events?organizer=acme&limit=5');

    $response->assertOk()
        ->assertJsonStructure([
            'organizer' => ['name'],
            'events' => [
                '*' => ['title', 'starts_at', 'url'],
            ],
        ])
        ->assertJsonFragment(['title' => 'Awesome Concert']);
});

it('includes CORS header on widget response', function (): void {
    $this->withoutMiddleware(\App\Http\Middleware\DetectCurrentOrganizer::class);
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'CORS Event',
        'slug' => 'cors-event',
    ]);

    $this->get('/api/widget/events?organizer=acme&limit=5')
        ->assertHeader('Access-Control-Allow-Origin', '*');
});

it('returns 404 for unknown organizer', function (): void {
    $this->getJson('/api/widget/events?organizer=unknown-organizer&limit=5')
        ->assertNotFound();
});

it('returns 422 for limit exceeding maximum', function (): void {
    $this->getJson('/api/widget/events?organizer=acme&limit=21')
        ->assertStatus(422);
});

it('returns 422 for limit below minimum', function (): void {
    $this->getJson('/api/widget/events?organizer=acme&limit=0')
        ->assertStatus(422);
});

it('returns empty events array for organizer with no published events', function (): void {
    $this->getJson('/api/widget/events?organizer=acme&limit=5')
        ->assertOk()
        ->assertJsonCount(0, 'events');
});

it('only includes events from requested organizer', function (): void {
    $otherOrganizer = Organizer::factory()->create(['name' => 'Other Org', 'slug' => 'other']);
    Event::factory()->create([
        'organizer_id' => $otherOrganizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Other Event',
        'slug' => 'other-event',
    ]);

    $this->getJson('/api/widget/events?organizer=acme&limit=5')
        ->assertOk()
        ->assertJsonCount(0, 'events');
});

it('excludes hidden events from widget', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
        'title' => 'Draft Event',
        'slug' => 'draft-event',
    ]);
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
        'title' => 'Private Event',
        'slug' => 'private-event',
    ]);

    $this->getJson('/api/widget/events?organizer=acme&limit=5')
        ->assertOk()
        ->assertJsonCount(0, 'events');
});

it('respects limit parameter', function (): void {
    Event::factory()->count(5)->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    $this->getJson('/api/widget/events?organizer=acme&limit=3')
        ->assertOk()
        ->assertJsonCount(3, 'events');
});

it('returns 400 when organizer parameter is missing', function (): void {
    $this->getJson('/api/widget/events?limit=5')
        ->assertStatus(400);
});
