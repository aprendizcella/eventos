<?php

declare(strict_types=1);

use App\Http\Requests\Venues\CreateVenueRequest;
use App\Http\Requests\Venues\UpdateVenueRequest;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([ValidateCsrfToken::class]);

    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

// =============================================================================
// CreateVenueRequest
// =============================================================================

it('validates required fields for create venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.venues.store', $organizer), []);

    $response->assertSessionHasErrors(['name', 'address']);
});

it('accepts valid data for create venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.venues.store', $organizer), [
            'name' => 'Valid Venue',
            'address' => '123 Valid St',
            'city' => 'Valid City',
            'capacity' => 200,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

it('rejects invalid capacity for create venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.venues.store', $organizer), [
            'name' => 'Bad Venue',
            'address' => '123 Bad St',
            'capacity' => -5,
        ]);

    $response->assertSessionHasErrors(['capacity']);
});

it('converts create request to DTO', function (): void {
    $request = new CreateVenueRequest;
    $request->merge([
        'name' => 'DTO Venue',
        'address' => 'DTO Address',
        'city' => 'DTO City',
        'capacity' => 300,
        'description' => 'DTO Description',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());
    $request->setValidator($validator);

    $dto = $request->toDto(42);

    expect($dto->organizerId)->toBe(42);
    expect($dto->name)->toBe('DTO Venue');
    expect($dto->address)->toBe('DTO Address');
    expect($dto->city)->toBe('DTO City');
    expect($dto->capacity)->toBe(300);
    expect($dto->description)->toBe('DTO Description');
});

// =============================================================================
// UpdateVenueRequest
// =============================================================================

it('validates required fields for update venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $venue = App\Models\Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($admin)
        ->put(route('organizers.venues.update', [$organizer, $venue]), []);

    $response->assertSessionHasErrors(['name', 'address']);
});

it('converts update request to DTO', function (): void {
    $request = new UpdateVenueRequest;
    $request->merge([
        'name' => 'Update DTO',
        'address' => 'Update Address',
    ]);

    $validator = resolve('validator')->make($request->all(), $request->rules());
    $request->setValidator($validator);

    $dto = $request->toDto();

    expect($dto->name)->toBe('Update DTO');
    expect($dto->address)->toBe('Update Address');
    expect($dto->city)->toBeNull();
    expect($dto->capacity)->toBeNull();
});
