<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use App\Policies\VenuePolicy;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

// =============================================================================
// Organizer Role Permissions
// =============================================================================

it('allows organizer admin to view venue in their organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->view($admin, $venue))->toBeTrue();
});

it('allows organizer editor to view venue in their organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->view($editor, $venue))->toBeTrue();
});

it('allows organizer viewer to view venue in their organizer', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->view($viewer, $venue))->toBeTrue();
});

it('allows organizer admin to create venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $policy = new VenuePolicy;

    expect($policy->create($admin, $organizer))->toBeTrue();
});

it('allows organizer editor to create venue', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $policy = new VenuePolicy;

    expect($policy->create($editor, $organizer))->toBeTrue();
});

it('denies organizer viewer from creating venue', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $policy = new VenuePolicy;

    expect($policy->create($viewer, $organizer))->toBeFalse();
});

it('allows organizer admin to update venue', function (): void {
    $organizer = Organizer::factory()->create();
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->update($admin, $venue))->toBeTrue();
});

it('allows organizer editor to update venue', function (): void {
    $organizer = Organizer::factory()->create();
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->update($editor, $venue))->toBeTrue();
});

it('denies organizer viewer from updating venue', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->update($viewer, $venue))->toBeFalse();
});

it('denies organizer viewer from deleting venue', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->delete($viewer, $venue))->toBeFalse();
});

// =============================================================================
// Global Admin Access
// =============================================================================

it('allows super_admin to view any venue', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->view($user, $venue))->toBeTrue();
});

it('allows super_admin to create venue in any organizer', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();

    $policy = new VenuePolicy;

    expect($policy->create($user, $organizer))->toBeTrue();
});

it('allows super_admin to update any venue', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->update($user, $venue))->toBeTrue();
});

it('allows platform_admin to view any venue', function (): void {
    $user = User::factory()->create();
    $user->assignRole('platform_admin');

    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->view($user, $venue))->toBeTrue();
});

// =============================================================================
// Cross-Organizer Isolation
// =============================================================================

it('denies admin of organizer A from viewing organizer B venue', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    $venueB = Venue::factory()->create(['organizer_id' => $organizerB->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->view($adminA, $venueB))->toBeFalse();
});

it('denies editor of organizer A from updating organizer B venue', function (): void {
    $organizerA = Organizer::factory()->create();
    $organizerB = Organizer::factory()->create();

    $editorA = User::factory()->create();
    $organizerA->users()->attach($editorA->id, ['role' => OrganizerRoles::Editor->value]);

    $venueB = Venue::factory()->create(['organizer_id' => $organizerB->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->update($editorA, $venueB))->toBeFalse();
});

it('denies non-member from viewing venue', function (): void {
    $user = User::factory()->create();

    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->getKey()]);

    $policy = new VenuePolicy;

    expect($policy->view($user, $venue))->toBeFalse();
});

// =============================================================================
// viewAny
// =============================================================================

it('allows organizer member to list venues', function (): void {
    $organizer = Organizer::factory()->create();
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $policy = new VenuePolicy;

    expect($policy->viewAny($viewer, $organizer))->toBeTrue();
});

it('denies non-member from listing venues', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();

    $policy = new VenuePolicy;

    expect($policy->viewAny($user, $organizer))->toBeFalse();
});

it('allows super_admin to list all venues', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::factory()->create();

    $policy = new VenuePolicy;

    expect($policy->viewAny($user, $organizer))->toBeTrue();
});
