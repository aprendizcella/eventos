<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates an event with minimum fields and defaults', function (): void {
    $event = Event::factory()->create();

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->status)->toBe(EventStatus::Draft)
        ->and($event->visibility)->toBe(EventVisibility::Private)
        ->and($event->organizer_id)->not->toBeNull();
});

it('enforces unique slug', function (): void {
    Event::factory()->create(['slug' => 'mi-evento']);

    expect(fn () => Event::factory()->create(['slug' => 'mi-evento']))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('belongs to an organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->for($organizer)->create();

    expect($event->organizer)->toBeInstanceOf(Organizer::class)
        ->and($event->organizer->id)->toBe($organizer->id);
});

it('optionally belongs to a category', function (): void {
    $category = Category::factory()->create();
    $event = Event::factory()->for($category, 'category')->create();

    expect($event->category)->toBeInstanceOf(Category::class)
        ->and($event->category->category_id)->toBe($category->category_id);
});

it('optionally belongs to a venue', function (): void {
    $venue = Venue::factory()->create();
    $event = Event::factory()->for($venue)->create();

    expect($event->venue)->toBeInstanceOf(Venue::class)
        ->and($event->venue->venue_id)->toBe($venue->venue_id);
});

it('category and venue are nullable', function (): void {
    $event = Event::factory()->create(['category_id' => null, 'venue_id' => null]);

    $event->refresh();

    expect($event->category_id)->toBeNull()
        ->and($event->venue_id)->toBeNull()
        ->and($event->category)->toBeNull()
        ->and($event->venue)->toBeNull();
});

it('casts status to EventStatus enum', function (): void {
    $event = Event::factory()->create(['status' => EventStatus::Published]);

    $event->refresh();

    expect($event->status)->toBe(EventStatus::Published);
});

it('casts visibility to EventVisibility enum', function (): void {
    $event = Event::factory()->create(['visibility' => EventVisibility::Public]);

    $event->refresh();

    expect($event->visibility)->toBe(EventVisibility::Public);
});

it('casts starts_at and ends_at to datetime', function (): void {
    $starts = now()->addWeek();
    $ends = now()->addWeek()->addHours(2);

    $event = Event::factory()->create([
        'starts_at' => $starts,
        'ends_at' => $ends,
    ]);

    $event->refresh();

    expect($event->starts_at)->not->toBeNull()
        ->and($event->ends_at)->not->toBeNull();
});

it('uses soft deletes', function (): void {
    $event = Event::factory()->create();
    $event->delete();

    expect(Event::query()->count())->toBe(0)
        ->and(Event::withTrashed()->count())->toBe(1);
});

it('factory produces valid event', function (): void {
    $event = Event::factory()->create();

    expect($event->event_id)->not->toBeNull()
        ->and($event->title)->not->toBeEmpty()
        ->and($event->slug)->not->toBeEmpty()
        ->and($event->organizer_id)->not->toBeNull();
});
