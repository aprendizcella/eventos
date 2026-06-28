<?php

declare(strict_types=1);

use App\Actions\Venues\CreateVenueAction;
use App\DataTransferObjects\Venues\CreateVenueDto;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates a venue with minimum fields via action', function (): void {
    $organizer = Organizer::factory()->create();

    $dto = new CreateVenueDto(
        organizerId: $organizer->getKey(),
        name: 'Test Venue',
        address: '123 Test St',
    );

    $venue = (new CreateVenueAction)($dto);

    expect($venue->organizer_id)->toBe($organizer->getKey());
    expect($venue->name)->toBe('Test Venue');
    expect($venue->address)->toBe('123 Test St');
    expect($venue->city)->toBeNull();
    expect($venue->capacity)->toBeNull();
    expect($venue->description)->toBeNull();
});

it('creates a venue with all fields via action', function (): void {
    $organizer = Organizer::factory()->create();

    $dto = new CreateVenueDto(
        organizerId: $organizer->getKey(),
        name: 'Full Venue',
        address: '456 Full Ave',
        city: 'Full City',
        capacity: 500,
        description: 'A great venue',
    );

    $venue = (new CreateVenueAction)($dto);

    expect($venue->city)->toBe('Full City');
    expect($venue->capacity)->toBe(500);
    expect($venue->description)->toBe('A great venue');
});
