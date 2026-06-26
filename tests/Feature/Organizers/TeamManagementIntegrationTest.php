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

    // Create organizer-scoped roles
    Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
});

// =============================================================================
// Team Member Addition Scenarios
// =============================================================================

it('allows organizer admin to add existing user as editor', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $newUser = User::factory()->create();
    $editorRole = Role::where('name', 'editor')->first();

    // Set current organizer context
    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $newUser->id,
        'role_id' => $editorRole->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('organizer_user', [
        'organizer_id' => $organizer->id,
        'user_id' => $newUser->id,
        'role_id' => $editorRole->id,
    ]);

    // Activity must be logged
    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_added')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['user_id'])->toBe($newUser->id)
        ->and($activity->properties['role_id'])->toBe($editorRole->id);
});

it('rejects adding already-existing member', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $existingUser = User::factory()->create();
    $editorRole = Role::where('name', 'editor')->first();
    $organizer->users()->attach($existingUser->id, ['role_id' => $editorRole->id]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $existingUser->id,
        'role_id' => $editorRole->id,
    ]);

    // Should fail due to unique constraint
    $response->assertSessionHasErrors();
});

it('rejects adding non-existent user', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $editorRole = Role::where('name', 'editor')->first();

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => 99999, // Non-existent
        'role_id' => $editorRole->id,
    ]);

    $response->assertSessionHasErrors('user_id');
});

it('denies team member addition to viewer', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $viewer = User::factory()->create();
    $viewerRole = Role::where('name', 'viewer')->first();
    $organizer->users()->attach($viewer->id, ['role_id' => $viewerRole->id]);

    $newUser = User::factory()->create();
    $editorRole = Role::where('name', 'editor')->first();

    $this->actingAs($viewer)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $newUser->id,
        'role_id' => $editorRole->id,
    ]);

    $response->assertForbidden();
});

// =============================================================================
// Team Member Removal Scenarios
// =============================================================================

it('allows admin to remove a member when multiple admins exist', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $member = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $editorRole = Role::where('name', 'editor')->first();

    $organizer->users()->attach($admin1->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($admin2->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($member->id, ['role_id' => $editorRole->id]);

    $this->actingAs($admin1)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->delete(route('organizers.team.destroy', [$organizer, $member]));

    $response->assertRedirect();

    $this->assertDatabaseMissing('organizer_user', [
        'organizer_id' => $organizer->id,
        'user_id' => $member->id,
    ]);

    // Activity must be logged
    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_removed')
        ->first();

    expect($activity)->not->toBeNull();
});

it('prevents removing last admin', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->delete(route('organizers.team.destroy', [$organizer, $admin]));

    // Should fail with business rule error
    $response->assertSessionHasErrors();
});

it('prevents admin from removing themselves as last admin', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->delete(route('organizers.team.destroy', [$organizer, $admin]));

    $response->assertSessionHasErrors();
});

// =============================================================================
// Team Member Role Change Scenarios
// =============================================================================

it('allows admin to change member role from editor to admin', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $editor = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $editorRole = Role::where('name', 'editor')->first();

    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($editor->id, ['role_id' => $editorRole->id]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->put(route('organizers.team.update', [$organizer, $editor]), [
        'user_id' => $editor->id,
        'role_id' => $adminRole->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('organizer_user', [
        'organizer_id' => $organizer->id,
        'user_id' => $editor->id,
        'role_id' => $adminRole->id,
    ]);

    // Activity must be logged
    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_role_changed')
        ->first();

    expect($activity)->not->toBeNull();
});

it('prevents demoting last admin', function (): void {
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $editorRole = Role::where('name', 'editor')->first();
    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->put(route('organizers.team.update', [$organizer, $admin]), [
        'user_id' => $admin->id,
        'role_id' => $editorRole->id,
    ]);

    $response->assertSessionHasErrors();
});

// =============================================================================
// Multi-Organizer Membership Scenarios
// =============================================================================

it('allows user to have different roles in different organizers', function (): void {
    $organizerA = Organizer::create(['name' => 'Org A', 'slug' => 'org-a']);
    $organizerB = Organizer::create(['name' => 'Org B', 'slug' => 'org-b']);
    $user = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $viewerRole = Role::where('name', 'viewer')->first();

    $organizerA->users()->attach($user->id, ['role_id' => $adminRole->id]);
    $organizerB->users()->attach($user->id, ['role_id' => $viewerRole->id]);

    // Verify user has different roles in different organizers
    $pivotA = $organizerA->users()->where('users.id', $user->id)->first()->pivot;
    $pivotB = $organizerB->users()->where('users.id', $user->id)->first()->pivot;

    expect($pivotA->role_id)->toBe($adminRole->id)
        ->and($pivotB->role_id)->toBe($viewerRole->id);
});
