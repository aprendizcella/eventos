<?php

declare(strict_types=1);

use App\Actions\Venues\UpdateVenueAction;
use App\DataTransferObjects\Venues\UpdateVenueDto;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('updates venue fields via action', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Old Name',
        'address' => 'Old Address',
    ]);

    $dto = new UpdateVenueDto(
        name: 'New Name',
        address: 'New Address',
        city: 'New City',
        capacity: 1000,
        description: 'Updated description',
    );

    $updated = (new UpdateVenueAction)($venue, $dto);

    expect($updated->name)->toBe('New Name');
    expect($updated->address)->toBe('New Address');
    expect($updated->city)->toBe('New City');
    expect($updated->capacity)->toBe(1000);
    expect($updated->description)->toBe('Updated description');
});

it('can clear optional fields via action', function (): void {
    $venue = Venue::factory()->create([
        'name' => 'Venue',
        'address' => 'Address',
        'city' => 'City',
        'capacity' => 500,
        'description' => 'Description',
    ]);

    $dto = new UpdateVenueDto(
        name: 'Venue',
        address: 'Address',
    );

    $updated = (new UpdateVenueAction)($venue, $dto);

    expect($updated->city)->toBeNull();
    expect($updated->capacity)->toBeNull();
    expect($updated->description)->toBeNull();
});
