<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use App\Services\Discovery\EventSearchService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->organizer = Organizer::factory()->create();
    $this->venue = Venue::factory()->create([
        'organizer_id' => $this->organizer->id,
        'city' => 'New York',
    ]);

    Event::factory()->create([
        'organizer_id' => $this->organizer->id,
        'venue_id' => $this->venue->venue_id,
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
        'title' => 'Fallback Search Event',
        'description' => 'This event tests the fallback mechanism.',
    ]);
});

it('falls back to eloquent like search when scout fails', function (): void {
    $service = resolve(EventSearchService::class);

    // With SCOUT_DRIVER=database in testing, the service should
    // still return results via the Eloquent fallback
    $results = $service->search(query: 'Fallback');

    expect($results->total())->toBeGreaterThan(0);
    expect($results->items()[0]->title)->toContain('Fallback');
});

it('logs a warning when scout search is unavailable', function (): void {
    Log::spy();

    $service = resolve(EventSearchService::class);
    $service->search(query: 'Fallback');

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message) => str_contains($message, 'Scout search'))
        ->atLeast()
        ->once();
});

it('returns empty results when fallback finds nothing', function (): void {
    $service = resolve(EventSearchService::class);

    $results = $service->search(query: 'NonExistentTermXYZ');

    expect($results->total())->toBe(0);
});

it('falls back without search query returns upcoming events by date', function (): void {
    $service = resolve(EventSearchService::class);

    $results = $service->search(query: '');

    expect($results->total())->toBeGreaterThan(0);
    expect($results->items()[0]->title)->toBe('Fallback Search Event');
});
