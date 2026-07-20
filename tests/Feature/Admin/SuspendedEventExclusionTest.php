<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Services\Discovery\EventSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('excludes suspended events from search results by default', function () {
    $publishedEvent = Event::factory()->create([
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Allowed Event',
    ]);

    $suspendedEvent = Event::factory()->create([
        'status' => EventStatus::Suspended,
        'visibility' => EventVisibility::Public,
        'title' => 'Forbidden Event',
    ]);

    $service = new EventSearchService;

    $results = $service->search();

    $ids = collect($results->items())->pluck('event_id')->toArray();

    expect($ids)->toContain($publishedEvent->event_id);
    expect($ids)->not->toContain($suspendedEvent->event_id);
});

it('ensures suspended events are not searchable in scout', function () {
    $suspendedEvent = Event::factory()->create([
        'status' => EventStatus::Suspended,
        'visibility' => EventVisibility::Public,
    ]);

    expect($suspendedEvent->shouldBeSearchable())->toBeFalse();
});
