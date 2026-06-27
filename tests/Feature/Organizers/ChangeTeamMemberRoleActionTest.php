<?php

declare(strict_types=1);

use App\Actions\Organizers\ChangeTeamMemberRoleAction;
use App\DataTransferObjects\Organizers\ChangeTeamMemberRoleDto;
use App\Exceptions\LastAdminCannotBeRemovedException;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    Role::create(['name' => 'viewer', 'guard_name' => 'web']);
});

it('changes a team member role', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $editorRole = Role::query()->where('name', 'editor')->first();
    $viewerRole = Role::query()->where('name', 'viewer')->first();

    $organizer->users()->attach($admin->id, ['role_id' => Role::query()->where('name', 'admin')->first()->id]);
    $organizer->users()->attach($user->id, ['role_id' => $editorRole->id]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $user->id,
        roleId: $viewerRole->id,
    );

    $action = resolve(ChangeTeamMemberRoleAction::class);
    $action($organizer, $dto, $admin);

    $organizer->refresh();
    $member = $organizer->users()->where('users.id', $user->id)->first();
    expect($member->pivot->role_id)->toBe($viewerRole->id);
});

it('logs activity when changing team member role', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $editorRole = Role::query()->where('name', 'editor')->first();
    $viewerRole = Role::query()->where('name', 'viewer')->first();

    $organizer->users()->attach($admin->id, ['role_id' => Role::query()->where('name', 'admin')->first()->id]);
    $organizer->users()->attach($user->id, ['role_id' => $editorRole->id]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $user->id,
        roleId: $viewerRole->id,
    );

    $action = resolve(ChangeTeamMemberRoleAction::class);
    $action($organizer, $dto, $admin);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_role_changed')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['user_id'])->toBe($user->id)
        ->and($activity->properties['new_role_id'])->toBe($viewerRole->id);
});

it('prevents demoting last admin', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::query()->where('name', 'admin')->first();
    $editorRole = Role::query()->where('name', 'editor')->first();

    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $admin->id,
        roleId: $editorRole->id,
    );

    $action = resolve(ChangeTeamMemberRoleAction::class);

    expect(fn () => $action($organizer, $dto, $admin))
        ->toThrow(LastAdminCannotBeRemovedException::class);
});

it('allows demoting admin if other admins exist', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $adminRole = Role::query()->where('name', 'admin')->first();
    $editorRole = Role::query()->where('name', 'editor')->first();

    $organizer->users()->attach($admin1->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($admin2->id, ['role_id' => $adminRole->id]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $admin2->id,
        roleId: $editorRole->id,
    );

    $action = resolve(ChangeTeamMemberRoleAction::class);
    $action($organizer, $dto, $admin1);

    $organizer->refresh();
    $member = $organizer->users()->where('users.id', $admin2->id)->first();
    expect($member->pivot->role_id)->toBe($editorRole->id);
});
