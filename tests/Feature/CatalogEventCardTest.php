<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Product;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->organizer = Organizer::factory()->create(['name' => 'Card Test Org']);
    $this->category = Category::factory()->create(['name' => 'Concerts', 'slug' => 'concerts']);
    $this->venue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'Miami',
    ]);
});

it('shows minimum price on event card when products have availability', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Priced Event',
        'starts_at' => '2026-08-01 20:00:00',
    ]);

    // Create a product with a price that has capacity
    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'title' => 'General Admission',
    ]);

    // Create a priced entry with available capacity
    $product->prices()->create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 29.99,
        'capacity' => 100,
        'quantity_sold' => 0,
    ]);

    // Create a higher price to verify min is shown
    $product->prices()->create([
        'product_id' => $product->id,
        'name' => 'VIP',
        'price' => 99.99,
        'capacity' => 20,
        'quantity_sold' => 5,
    ]);

    Livewire::test('public.events.event-list-public')
        ->assertSee('Priced Event')
        ->assertSee('29.99');
});

it('shows sold out on event card when all products are sold out', function (): void {
    $event = Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'category_id' => $this->category->category_id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Sold Out Event',
        'starts_at' => '2026-09-01 20:00:00',
    ]);

    $product = Product::factory()->create([
        'event_id' => $event->event_id,
        'title' => 'General Admission',
    ]);

    // Price with capacity fully sold
    $product->prices()->create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 49.99,
        'capacity' => 10,
        'quantity_sold' => 10,
    ]);

    Livewire::test('public.events.event-list-public')
        ->assertSee('Sold Out Event')
        ->assertSee('Sold out')
        ->assertDontSee('49.99');
});
