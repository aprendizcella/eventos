<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
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
// List Venues
// =============================================================================

it('allows organizer admin to list venues', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    Venue::factory()->count(3)->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.venues.index', $organizer));

    $response->assertOk();
    $response->assertSee('Venues');
});

it('allows organizer viewer to list venues', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    Venue::factory()->count(2)->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($viewer)
        ->get(route('organizers.venues.index', $organizer));

    $response->assertOk();
});

it('denies non-member from listing venues', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('organizers.venues.index', $organizer));

    $response->assertForbidden();
});

it('shows only venues belonging to the organizer', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $admin = User::factory()->create();
    $organizerA->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    Venue::factory()->create(['organizer_id' => $organizerA->getKey(), 'name' => 'Venue A']);
    Venue::factory()->create(['organizer_id' => $organizerB->getKey(), 'name' => 'Venue B']);

    $response = $this->actingAs($admin)
        ->get(route('organizers.venues.index', $organizerA));

    $response->assertOk();
    $response->assertSee('Venue A');
    $response->assertDontSee('Venue B');
});

// =============================================================================
// Create Venue
// =============================================================================

it('allows organizer admin to view create form', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.venues.create', $organizer));

    $response->assertOk();
});

it('allows organizer editor to view create form', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $response = $this->actingAs($editor)
        ->get(route('organizers.venues.create', $organizer));

    $response->assertOk();
});

it('denies organizer viewer from viewing create form', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $response = $this->actingAs($viewer)
        ->get(route('organizers.venues.create', $organizer));

    $response->assertForbidden();
});

it('allows organizer admin to store venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.venues.store', $organizer), [
            'name' => 'Test Venue',
            'address' => '123 Test St',
            'city' => 'Test City',
            'capacity' => 500,
        ]);

    $response->assertRedirect(route('organizers.venues.index', $organizer));

    $this->assertDatabaseHas('venue', [
        'organizer_id' => $organizer->getKey(),
        'name' => 'Test Venue',
        'address' => '123 Test St',
        'city' => 'Test City',
        'capacity' => 500,
    ]);
});

it('allows organizer editor to store venue', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $response = $this->actingAs($editor)
        ->post(route('organizers.venues.store', $organizer), [
            'name' => 'Editor Venue',
            'address' => '456 Editor Ave',
        ]);

    $response->assertRedirect(route('organizers.venues.index', $organizer));

    $this->assertDatabaseHas('venue', [
        'organizer_id' => $organizer->getKey(),
        'name' => 'Editor Venue',
    ]);
});

it('denies organizer viewer from storing venue', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $response = $this->actingAs($viewer)
        ->post(route('organizers.venues.store', $organizer), [
            'name' => 'Viewer Venue',
            'address' => '789 Viewer Blvd',
        ]);

    $response->assertForbidden();
});

it('validates required fields when creating venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)
        ->post(route('organizers.venues.store', $organizer), []);

    $response->assertSessionHasErrors(['name', 'address']);
});

// =============================================================================
// Update Venue
// =============================================================================

it('allows organizer admin to view edit form', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($admin)
        ->get(route('organizers.venues.edit', [$organizer, $venue]));

    $response->assertOk();
});

it('allows organizer editor to update venue', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $venue = Venue::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'name' => 'Old Name',
        'address' => 'Old Address',
    ]);

    $response = $this->actingAs($editor)
        ->put(route('organizers.venues.update', [$organizer, $venue]), [
            'name' => 'New Name',
            'address' => 'New Address',
            'city' => 'New City',
            'capacity' => 1000,
        ]);

    $response->assertRedirect(route('organizers.venues.index', $organizer));

    $venue->refresh();
    expect($venue->name)->toBe('New Name');
    expect($venue->address)->toBe('New Address');
    expect($venue->city)->toBe('New City');
    expect($venue->capacity)->toBe(1000);
});

it('denies organizer viewer from updating venue via route', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($viewer)
        ->put(route('organizers.venues.update', [$organizer, $venue]), [
            'name' => 'Hacked',
            'address' => 'Hacked',
        ]);

    $response->assertForbidden();
});

// =============================================================================
// Cross-Organizer Denial
// =============================================================================

it('denies admin of organizer A from accessing organizer B venue list', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    Venue::factory()->count(2)->create(['organizer_id' => $organizerB->getKey()]);

    $response = $this->actingAs($adminA)
        ->get(route('organizers.venues.index', $organizerB));

    $response->assertForbidden();
});

it('denies admin of organizer A from editing organizer B venue', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    $venueB = Venue::factory()->create(['organizer_id' => $organizerB->getKey()]);

    $response = $this->actingAs($adminA)
        ->put(route('organizers.venues.update', [$organizerB, $venueB]), [
            'name' => 'Hacked',
            'address' => 'Hacked',
        ]);

    $response->assertForbidden();
});

it('returns 404 when venue does not belong to organizer in URL', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    // Venue belongs to organizerA, but URL says organizerB
    $venueA = Venue::factory()->create(['organizer_id' => $organizerA->getKey()]);

    // Admin of A tries to edit venue of A but under organizer B's URL
    // First, attach admin to B too so they pass the organizer.detect check
    $organizerB->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($adminA)
        ->put(route('organizers.venues.update', [$organizerB, $venueA]), [
            'name' => 'Hacked',
            'address' => 'Hacked',
        ]);

    $response->assertNotFound();
});

// =============================================================================
// Global Admin
// =============================================================================

it('allows super_admin to list venues of any organizer', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    Venue::factory()->count(2)->create(['organizer_id' => $organizer->getKey()]);

    $response = $this->actingAs($user)
        ->get(route('organizers.venues.index', $organizer));

    $response->assertOk();
});

it('allows super_admin to create venue in any organizer', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('organizers.venues.store', $organizer), [
            'name' => 'Global Venue',
            'address' => 'Global Address',
        ]);

    $response->assertRedirect(route('organizers.venues.index', $organizer));

    $this->assertDatabaseHas('venue', [
        'organizer_id' => $organizer->getKey(),
        'name' => 'Global Venue',
    ]);
});

it('allows super_admin to update any venue', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create([
        'organizer_id' => $organizer->getKey(),
        'name' => 'Old Name',
        'address' => 'Old Address',
    ]);

    $response = $this->actingAs($user)
        ->put(route('organizers.venues.update', [$organizer, $venue]), [
            'name' => 'New Name',
            'address' => 'New Address',
        ]);

    $response->assertRedirect(route('organizers.venues.index', $organizer));

    $venue->refresh();
    expect($venue->name)->toBe('New Name');
});
