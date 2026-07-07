<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// --------------------------------------------------------------------------
// Task 3.2 — Cross-organizer data isolation via forOrganizer scope
// --------------------------------------------------------------------------

it('isolates events between organizers using forOrganizer scope', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $eventA = Event::factory()->create(['organizer_id' => $organizerA->id]);
    $eventB = Event::factory()->create(['organizer_id' => $organizerB->id]);

    $eventsForA = Event::query()->forOrganizer($organizerA->id)->get();
    $eventsForB = Event::query()->forOrganizer($organizerB->id)->get();

    expect($eventsForA)->toHaveCount(1);
    expect($eventsForA->first()->event_id)->toBe($eventA->event_id);
    expect($eventsForB)->toHaveCount(1);
    expect($eventsForB->first()->event_id)->toBe($eventB->event_id);
});

it('prevents cross-organizer event access by organizer_id', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    Event::factory()->count(2)->create(['organizer_id' => $organizerA->id]);
    Event::factory()->count(3)->create(['organizer_id' => $organizerB->id]);

    $eventsForA = Event::query()->forOrganizer($organizerA->id)->get();
    $eventsForB = Event::query()->forOrganizer($organizerB->id)->get();

    // Organizer A should not see B's events
    expect($eventsForA)->toHaveCount(2);
    expect($eventsForA->pluck('organizer_id')->unique()->all())->toBe([$organizerA->id]);

    // Organizer B should not see A's events
    expect($eventsForB)->toHaveCount(3);
    expect($eventsForB->pluck('organizer_id')->unique()->all())->toBe([$organizerB->id]);
});

it('isolates venues between organizers using forOrganizer scope', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $venueA = Venue::factory()->create(['organizer_id' => $organizerA->id]);
    $venueB = Venue::factory()->create(['organizer_id' => $organizerB->id]);

    $venuesForA = Venue::query()->forOrganizer($organizerA->id)->get();
    $venuesForB = Venue::query()->forOrganizer($organizerB->id)->get();

    expect($venuesForA)->toHaveCount(1);
    expect($venuesForA->first()->venue_id)->toBe($venueA->venue_id);
    expect($venuesForB)->toHaveCount(1);
    expect($venuesForB->first()->venue_id)->toBe($venueB->venue_id);
});

it('prevents cross-organizer venue access by organizer_id', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    Venue::factory()->count(2)->create(['organizer_id' => $organizerA->id]);
    Venue::factory()->count(1)->create(['organizer_id' => $organizerB->id]);

    $venuesForA = Venue::query()->forOrganizer($organizerA->id)->get();
    $venuesForB = Venue::query()->forOrganizer($organizerB->id)->get();

    expect($venuesForA)->toHaveCount(2);
    expect($venuesForA->pluck('organizer_id')->unique()->all())->toBe([$organizerA->id]);
    expect($venuesForB)->toHaveCount(1);
    expect($venuesForB->pluck('organizer_id')->unique()->all())->toBe([$organizerB->id]);
});

// --------------------------------------------------------------------------
// Task 3.2 — Superadmin global context
// --------------------------------------------------------------------------

it('keeps the APP_URL root domain tenant-less for global routes', function (): void {
    $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    $this->get("http://{$appUrlHost}/")
        ->assertSuccessful();

    expect(Organizer::checkCurrent())->toBeFalse();
});

it('keeps login route tenant-less on APP_URL host', function (): void {
    $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    $this->get("http://{$appUrlHost}/login")
        ->assertSuccessful();

    expect(Organizer::checkCurrent())->toBeFalse();
});
