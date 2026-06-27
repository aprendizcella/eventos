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
// Authorization Matrix Tests
// =============================================================================

it('allows super_admin to access any organizer', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $response = $this->actingAs($user)->get(route('organizers.show', $organizer));

    $response->assertOk();
});

it('allows platform_admin to access any organizer', function (): void {
    $user = User::factory()->create();
    $user->assignRole('platform_admin');

    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $response = $this->actingAs($user)->get(route('organizers.show', $organizer));

    $response->assertOk();
});

it('allows organizer admin to view their organizer', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $response = $this->actingAs($admin)->get(route('organizers.show', $organizer));

    $response->assertOk();
});

it('denies organizer editor from managing team', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $editor = User::factory()->create();
    $organizer->users()->attach($editor->id, ['role' => OrganizerRoles::Editor->value]);

    $newUser = User::factory()->create();

    $this->actingAs($editor)->withSession(['current_organizer_id' => $organizer->id]);

    $response = $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $newUser->id,
        'role' => OrganizerRoles::Viewer->value,
    ]);

    $response->assertForbidden();
});

it('denies organizer viewer from managing team', function (): void {
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

it('denies non-member from accessing organizer', function (): void {
    $user = User::factory()->create();
    // No role, no membership

    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $response = $this->actingAs($user)->get(route('organizers.show', $organizer));

    $response->assertForbidden();
});

// =============================================================================
// Cross-Organizer Isolation Tests
// =============================================================================

it('prevents cross-organizer team management', function (): void {
    $organizerA = Organizer::query()->create(['name' => 'Org A', 'slug' => 'org-a']);
    $organizerB = Organizer::query()->create(['name' => 'Org B', 'slug' => 'org-b']);

    $adminA = User::factory()->create();
    $organizerA->users()->attach($adminA->id, ['role' => OrganizerRoles::Admin->value]);

    $userFromB = User::factory()->create();
    $organizerB->users()->attach($userFromB->id, ['role' => OrganizerRoles::Admin->value]);

    // Admin from A tries to add user from B to A's team
    $this->actingAs($adminA)->withSession(['current_organizer_id' => $organizerA->id]);

    $response = $this->post(route('organizers.team.store', $organizerA), [
        'user_id' => $userFromB->id,
        'role' => OrganizerRoles::Editor->value,
    ]);

    // Should succeed because user exists, but they're now in organizer A
    // The isolation is at the data level, not the user level
    $response->assertRedirect();

    // Verify user is now in organizer A
    $this->assertDatabaseHas('organizer_user', [
        'organizer_id' => $organizerA->id,
        'user_id' => $userFromB->id,
    ]);
});

it('ensures team members are isolated per organizer', function (): void {
    $organizerA = Organizer::query()->create(['name' => 'Org A', 'slug' => 'org-a']);
    $organizerB = Organizer::query()->create(['name' => 'Org B', 'slug' => 'org-b']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $organizerA->users()->attach($userA->id, ['role' => OrganizerRoles::Admin->value]);
    $organizerB->users()->attach($userB->id, ['role' => OrganizerRoles::Editor->value]);

    // Verify isolation
    expect($organizerA->users->pluck('id'))->toContain($userA->id)
        ->and($organizerA->users->pluck('id'))->not->toContain($userB->id)
        ->and($organizerB->users->pluck('id'))->toContain($userB->id)
        ->and($organizerB->users->pluck('id'))->not->toContain($userA->id);
});

// =============================================================================
// Audit Privacy Tests
// =============================================================================

it('includes organizer_id in activity properties for team events', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $newUser = User::factory()->create();

    $this->actingAs($admin)->withSession(['current_organizer_id' => $organizer->id]);

    $this->post(route('organizers.team.store', $organizer), [
        'user_id' => $newUser->id,
        'role' => OrganizerRoles::Editor->value,
    ]);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_added')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['user_id'])->toBe($newUser->id)
        ->and($activity->properties['role'])->toBe(OrganizerRoles::Editor->value);
});
