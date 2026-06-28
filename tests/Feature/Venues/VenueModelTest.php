<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates a venue with required fields', function (): void {
    $venue = Venue::factory()->create();

    expect($venue)->toBeInstanceOf(Venue::class)
        ->and($venue->name)->not->toBeEmpty()
        ->and($venue->address)->not->toBeEmpty()
        ->and($venue->organizer_id)->not->toBeNull();
});

it('belongs to an organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->for($organizer)->create();

    expect($venue->organizer)->toBeInstanceOf(Organizer::class)
        ->and($venue->organizer->id)->toBe($organizer->id);
});

it('uses soft deletes', function (): void {
    $venue = Venue::factory()->create();
    $venue->delete();

    expect(Venue::query()->count())->toBe(0)
        ->and(Venue::withTrashed()->count())->toBe(1);
});

it('factory produces valid venue linked to organizer', function (): void {
    $venue = Venue::factory()->create();

    expect($venue->organizer_id)->not->toBeNull()
        ->and($venue->name)->not->toBeEmpty()
        ->and($venue->address)->not->toBeEmpty();
});

it('casts capacity to integer when provided', function (): void {
    $venue = Venue::factory()->create(['capacity' => 500]);

    $venue->refresh();

    expect($venue->capacity)->toBe(500);
});
