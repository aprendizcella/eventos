<?php

declare(strict_types=1);

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

    // Set team context for global roles (using 0 as sentinel for "no specific team")
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    // Create global roles only — organizer roles are domain-owned via OrganizerRoles enum
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);
});

// =============================================================================
// Team Member Addition Scenarios
// =============================================================================

it('allows organizer admin to add existing user as editor', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $newUser = User::factory()->create();

    // Set current organizer context
    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $newUser->id,
        'role' => OrganizerRoles::Editor->value,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('organizer_user', [
        'organizer_id' => $organizer->id,
        'user_id' => $newUser->id,
        'role' => OrganizerRoles::Editor->value,
    ]);

    // Activity must be logged
    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_added')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['user_id'])->toBe($newUser->id)
        ->and($activity->properties['role'])->toBe(OrganizerRoles::Editor->value);
});

it('rejects adding already-existing member', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $existingUser = User::factory()->create();
    $organizer->users()->attach($existingUser->id, ['role' => OrganizerRoles::Editor->value]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $existingUser->id,
        'role' => OrganizerRoles::Editor->value,
    ]);

    // Should fail due to unique constraint
    $response->assertSessionHasErrors();
});

it('rejects adding non-existent user', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => 99999, // Non-existent
        'role' => OrganizerRoles::Editor->value,
    ]);

    $response->assertSessionHasErrors('user_id');
});

it('denies team member addition to viewer', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $viewer = User::factory()->create();
    $organizer->users()->attach($viewer->id, ['role' => OrganizerRoles::Viewer->value]);

    $newUser = User::factory()->create();

    $this->actingAs($viewer)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $newUser->id,
        'role' => OrganizerRoles::Editor->value,
    ]);

    $response->assertForbidden();
});

// =============================================================================
// Team Member Removal Scenarios
// =============================================================================

it('allows admin to remove a member when multiple admins exist', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $member = User::factory()->create();

    $organizer->users()->attach($admin1->id, ['role' => OrganizerRoles::Admin->value]);
    $organizer->users()->attach($admin2->id, ['role' => OrganizerRoles::Admin->value]);
    $organizer->users()->attach($member->id, ['role' => OrganizerRoles::Editor->value]);

    $this->actingAs($admin1)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->delete(route('organizers.team.destroy', [$organizer, $member]));

    $response->assertRedirect();

    $this->assertDatabaseMissing('organizer_user', [
        'organizer_id' => $organizer->id,
        'user_id' => $member->id,
    ]);

    // Activity must be logged
    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_removed')
        ->first();

    expect($activity)->not->toBeNull();
});

it('prevents removing last admin', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->delete(route('organizers.team.destroy', [$organizer, $admin]));

    // Should fail with business rule error
    $response->assertSessionHasErrors();
});

it('prevents admin from removing themselves as last admin', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->delete(route('organizers.team.destroy', [$organizer, $admin]));

    $response->assertSessionHasErrors();
});

// =============================================================================
// Team Member Role Change Scenarios
// =============================================================================

it('allows admin to change member role from editor to admin', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $editor = User::factory()->create();

    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->put(route('organizers.team.update', [$organizer, $editor]), [
        'user_id' => $editor->id,
        'role' => OrganizerRoles::Admin->value,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('organizer_user', [
        'organizer_id' => $organizer->id,
        'user_id' => $editor->id,
        'role' => OrganizerRoles::Admin->value,
    ]);

    // Activity must be logged
    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_role_changed')
        ->first();

    expect($activity)->not->toBeNull();
});

it('prevents demoting last admin', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->put(route('organizers.team.update', [$organizer, $admin]), [
        'user_id' => $admin->id,
        'role' => OrganizerRoles::Editor->value,
    ]);

    $response->assertSessionHasErrors();
});

// =============================================================================
// Multi-Organizer Membership Scenarios
// =============================================================================

it('allows user to have different roles in different organizers', function (): void {
    $organizerA = Organizer::query()->create(['name' => 'Org A', 'slug' => 'org-a']);
    $organizerB = Organizer::query()->create(['name' => 'Org B', 'slug' => 'org-b']);
    $user = User::factory()->create();

    $organizerA->users()->attach($user->id, ['role' => OrganizerRoles::Admin->value]);
    $organizerB->users()->attach($user->id, ['role' => OrganizerRoles::Viewer->value]);

    // Verify user has different roles in different organizers
    $pivotA = $organizerA->users()->where('users.id', $user->id)->first()->pivot;
    $pivotB = $organizerB->users()->where('users.id', $user->id)->first()->pivot;

    expect($pivotA->role)->toBe(OrganizerRoles::Admin->value)
        ->and($pivotB->role)->toBe(OrganizerRoles::Viewer->value);
});
