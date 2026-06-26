<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware([ValidateCsrfToken::class]);

    // Set team context for global roles (using 0 as sentinel for "no specific team")
    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    // Create global roles
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'attendee', 'guard_name' => 'web']);

    // Create organizer-scoped roles
    Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
});

// =============================================================================
// Organizer Creation Scenarios
// =============================================================================

it('allows super_admin to create organizer with valid data', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->post(route('organizers.store'), [
        'name' => 'Test Organizer',
        'slug' => 'test-organizer',
        'domain' => 'test.example.com',
        'status' => 'active',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('organizers', [
        'name' => 'Test Organizer',
        'slug' => 'test-organizer',
        'domain' => 'test.example.com',
        'status' => 'active',
    ]);

    // Activity log must record the creation
    $organizer = Organizer::where('slug', 'test-organizer')->first();
    expect($organizer)->not->toBeNull();

    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull();
});

it('rejects duplicate slug on organizer creation', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Organizer::create(['name' => 'Existing', 'slug' => 'fest-x']);

    $response = $this->actingAs($user)->post(route('organizers.store'), [
        'name' => 'New Organizer',
        'slug' => 'fest-x',
    ]);

    $response->assertSessionHasErrors('slug');
});

it('denies organizer creation to non-admin users', function (): void {
    $user = User::factory()->create();
    // No role assigned

    $response = $this->actingAs($user)->post(route('organizers.store'), [
        'name' => 'Test',
        'slug' => 'test',
    ]);

    $response->assertForbidden();
});

// =============================================================================
// Organizer Listing Scenarios
// =============================================================================

it('allows admin to list organizers', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Organizer::create(['name' => 'Org 1', 'slug' => 'org-1']);
    Organizer::create(['name' => 'Org 2', 'slug' => 'org-2']);

    $response = $this->actingAs($user)->get(route('organizers.index'));

    $response->assertOk()
        ->assertSee('Org 1')
        ->assertSee('Org 2');
});

it('shows inactive organizers as distinguishable in list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    Organizer::create(['name' => 'Active Org', 'slug' => 'active', 'status' => 'active']);
    Organizer::create(['name' => 'Inactive Org', 'slug' => 'inactive', 'status' => 'inactive']);

    $response = $this->actingAs($user)->get(route('organizers.index'));

    $response->assertOk()
        ->assertSee('Active')
        ->assertSee('Inactive');
});

// =============================================================================
// Organizer Update Scenarios
// =============================================================================

it('allows super_admin to update organizer name', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::create(['name' => 'Old Name', 'slug' => 'old-name']);

    $response = $this->actingAs($user)->put(route('organizers.update', $organizer), [
        'name' => 'New Name',
        'slug' => 'old-name',
    ]);

    $response->assertRedirect();

    $organizer->refresh();
    expect($organizer->name)->toBe('New Name');

    // Activity must be logged
    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull();
});

it('rejects update to existing slug', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizerA = Organizer::create(['name' => 'Org A', 'slug' => 'alpha']);
    Organizer::create(['name' => 'Org B', 'slug' => 'beta']);

    $response = $this->actingAs($user)->put(route('organizers.update', $organizerA), [
        'name' => 'Org A',
        'slug' => 'beta', // Already exists
    ]);

    $response->assertSessionHasErrors('slug');
});

// =============================================================================
// Organizer Status Toggle Scenarios
// =============================================================================

it('allows deactivating an organizer without deleting data', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test', 'status' => 'active']);

    $response = $this->actingAs($user)->put(route('organizers.update', $organizer), [
        'name' => 'Test',
        'slug' => 'test',
        'status' => 'inactive',
    ]);

    $response->assertRedirect();

    $organizer->refresh();
    expect($organizer->status)->toBe('inactive');

    // Data must remain intact
    $this->assertDatabaseHas('organizers', [
        'id' => $organizer->id,
        'name' => 'Test',
        'status' => 'inactive',
    ]);
});

it('allows reactivating an organizer', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test', 'status' => 'inactive']);

    $response = $this->actingAs($user)->put(route('organizers.update', $organizer), [
        'name' => 'Test',
        'slug' => 'test',
        'status' => 'active',
    ]);

    $response->assertRedirect();

    $organizer->refresh();
    expect($organizer->status)->toBe('active');
});
