<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use App\Services\Discovery\EventSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\EngineManager;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('flushes catalog cache when an event is created, updated or deleted', function () {
    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $organizer = Organizer::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();

    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $event->update(['title' => 'New title']);
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();

    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $event->delete();
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();
});

it('flushes catalog cache when a category is created, updated or deleted', function () {
    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $cat = Category::factory()->create();
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();

    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $cat->update(['name' => 'New name']);
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();

    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $cat->delete();
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();
});

it('flushes catalog cache when a venue is created, updated or deleted', function () {
    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->id]);
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();

    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $venue->update(['name' => 'New venue']);
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();

    Cache::tags(['catalog'])->put('test_key', 'test_value');
    $venue->delete();
    expect(Cache::tags(['catalog'])->has('test_key'))->toBeFalse();
});

it('serves repeated searches from cache when scout fallback is used', function () {
    $service = resolve(EventSearchService::class);

    // Force Scout to throw an exception to trigger fallback
    $mockEngine = Mockery::mock(Laravel\Scout\Engines\Engine::class);
    $mockEngine->shouldReceive('paginate')->andThrow(new Exception('Scout failed'));
    $mockEngine->shouldReceive('delete')->andReturn(null);
    $mockEngine->shouldReceive('update')->andReturn(null);
    resolve(EngineManager::class)->extend('mock', fn () => $mockEngine);
    config(['scout.driver' => 'mock']);

    Cache::tags(['catalog'])->flush();
    expect(Cache::tags(['catalog'])->get('catalog:search:'.md5('testa:1:{s:4:"date";s:10:"2026-08-15";}|page=1|perPage=12')))->toBeNull();

    $results1 = $service->search('testa', ['date' => '2026-08-15']);

    // The query should be cached now
    $cached = Cache::tags(['catalog'])->get('catalog:search:'.md5('testa'.serialize(['date' => '2026-08-15']).'|page=1|perPage=12'));
    expect($cached)->not->toBeNull();

    $results2 = $service->search('testa', ['date' => '2026-08-15']);
    expect($results2->total())->toBe($results1->total());
});

it('reuses the cached fallback result without a second database query', function () {
    $service = resolve(EventSearchService::class);

    $mockEngine = Mockery::mock(Laravel\Scout\Engines\Engine::class);
    $mockEngine->shouldReceive('paginate')->andThrow(new Exception('Scout failed'));
    $mockEngine->shouldReceive('delete')->andReturn(null);
    $mockEngine->shouldReceive('update')->andReturn(null);
    resolve(EngineManager::class)->extend('mock-cache', fn () => $mockEngine);
    config(['scout.driver' => 'mock-cache']);

    Cache::tags(['catalog'])->flush();
    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $service->search('cached query');
    $queriesAfterFirstCall = $queryCount;

    $service->search('cached query');

    expect($queriesAfterFirstCall)->toBeGreaterThan(0)
        ->and($queryCount)->toBe($queriesAfterFirstCall);
});

it('logs a warning when search fallback is triggered', function () {
    $service = resolve(EventSearchService::class);

    $mockEngine = Mockery::mock(Laravel\Scout\Engines\Engine::class);
    $mockEngine->shouldReceive('paginate')->andThrow(new Exception('Scout failed dummy'));
    $mockEngine->shouldReceive('delete')->andReturn(null);
    $mockEngine->shouldReceive('update')->andReturn(null);
    resolve(EngineManager::class)->extend('mock', fn () => $mockEngine);
    config(['scout.driver' => 'mock']);

    Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => str_contains((string) $message, 'Scout search unavailable, falling back to Eloquent LIKE') && $context['error'] === 'Scout failed dummy');

    $service->search('test log', []);
});

it('caches metadata lookups for categories and cities in public catalog', function () {
    Livewire\Volt\Volt::test('public.events.event-list-public');

    expect(Cache::tags(['catalog'])->has('catalog:categories'))->toBeTrue();
    expect(Cache::tags(['catalog'])->has('catalog:cities'))->toBeTrue();
});
