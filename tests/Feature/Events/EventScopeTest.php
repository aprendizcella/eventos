<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('scopePublic returns only events with public visibility', function (): void {
    $organizer = Organizer::factory()->create();

    $publicEvent = Event::factory()->create([
        'organizer_id' => $organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    $privateEvent = Event::factory()->create([
        'organizer_id' => $organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Private,
    ]);

    $results = Event::query()->public()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->event_id)->toBe($publicEvent->event_id);
});

it('scopePublic excludes password protected events', function (): void {
    $organizer = Organizer::factory()->create();

    Event::factory()->create([
        'organizer_id' => $organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::PasswordProtected,
    ]);

    $results = Event::query()->public()->get();

    expect($results)->toHaveCount(0);
});

it('scopePublic can be chained with published scope', function (): void {
    $organizer = Organizer::factory()->create();

    $publishedPublic = Event::factory()->create([
        'organizer_id' => $organizer->id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    // Draft + public — should NOT appear (not published)
    Event::factory()->create([
        'organizer_id' => $organizer->id,
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Public,
    ]);

    $results = Event::query()->published()->public()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->event_id)->toBe($publishedPublic->event_id);
});
