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

it('returns XML sitemap with correct content type', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Public Event',
        'slug' => 'public-event',
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8');
});

it('includes published public events in sitemap', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Included Event',
        'slug' => 'included-event',
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertSee('included-event', false);
});

it('excludes private events from sitemap', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
        'title' => 'Private Event',
        'slug' => 'private-event',
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee('private-event', false);
});

it('excludes unpublished events from sitemap', function (): void {
    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
        'title' => 'Draft Event',
        'slug' => 'draft-event',
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee('draft-event', false);
});

it('returns valid XML sitemap when no eligible events exist', function (): void {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('<urlset', false);
});
