<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->category = Category::factory()->create(['name' => 'Music', 'slug' => 'music']);
    $this->category2 = Category::factory()->create(['name' => 'Tech', 'slug' => 'tech']);
});

it('shows all published public events from all organizers on root domain', function (): void {
    $organizer1 = Organizer::factory()->create();
    $organizer2 = Organizer::factory()->create();

    $event1 = Event::factory()->create([
        'organizer_id' => $organizer1->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Event from Organizer 1',
    ]);

    $event2 = Event::factory()->create([
        'organizer_id' => $organizer2->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Event from Organizer 2',
    ]);

    Livewire::test('public.events.event-list-public')
        ->assertSee('Event from Organizer 1')
        ->assertSee('Event from Organizer 2');
});

it('shows only current organizer events on tenant domain', function (): void {
    $organizer1 = Organizer::factory()->create();
    $organizer2 = Organizer::factory()->create();

    Event::factory()->create([
        'organizer_id' => $organizer1->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Organizer 1 Event',
    ]);

    Event::factory()->create([
        'organizer_id' => $organizer2->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Organizer 2 Event',
    ]);

    // Act as tenant (organizer1)
    $organizer1->makeCurrent();

    Livewire::test('public.events.event-list-public')
        ->assertSee('Organizer 1 Event')
        ->assertDontSee('Organizer 2 Event');
});

it('hides private events from the catalog', function (): void {
    $organizer = Organizer::factory()->create();

    Event::factory()->create([
        'organizer_id' => $organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Public Event',
    ]);

    Event::factory()->create([
        'organizer_id' => $organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
        'title' => 'Private Event',
    ]);

    Livewire::test('public.events.event-list-public')
        ->assertSee('Public Event')
        ->assertDontSee('Private Event');
});

it('hides unpublished events from the catalog', function (): void {
    $organizer = Organizer::factory()->create();

    Event::factory()->create([
        'organizer_id' => $organizer->id,
        'category_id' => $this->category->category_id,
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
        'title' => 'Draft Event',
    ]);

    Livewire::test('public.events.event-list-public')
        ->assertDontSee('Draft Event');
});
