<?php

declare(strict_types=1);

use App\Actions\Organizers\ChangeTeamMemberRoleAction;
use App\DataTransferObjects\Organizers\ChangeTeamMemberRoleDto;
use App\Exceptions\LastAdminCannotBeRemovedException;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('changes a team member role', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();

    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Editor->value]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $user->id,
        role: OrganizerRoles::Viewer->value,
    );

    $action = resolve(ChangeTeamMemberRoleAction::class);
    $action($organizer, $dto, $admin);

    $organizer->refresh();
    $member = $organizer->users()->where('users.id', $user->id)->first();
    expect($member->pivot->role)->toBe(OrganizerRoles::Viewer->value);
});

it('logs activity when changing team member role', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();

    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);
    $organizer->users()->attach($user->id, ['role' => OrganizerRoles::Editor->value]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $user->id,
        role: OrganizerRoles::Viewer->value,
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
        ->and($activity->properties['new_role'])->toBe(OrganizerRoles::Viewer->value);
});

it('prevents demoting last admin', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();

    $organizer->users()->attach($admin->id, ['role' => OrganizerRoles::Admin->value]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $admin->id,
        role: OrganizerRoles::Editor->value,
    );

    $action = resolve(ChangeTeamMemberRoleAction::class);

    expect(fn () => $action($organizer, $dto, $admin))
        ->toThrow(LastAdminCannotBeRemovedException::class);
});

it('allows demoting admin if other admins exist', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();

    $organizer->users()->attach($admin1->id, ['role' => OrganizerRoles::Admin->value]);
    $organizer->users()->attach($admin2->id, ['role' => OrganizerRoles::Admin->value]);

    $dto = new ChangeTeamMemberRoleDto(
        userId: $admin2->id,
        role: OrganizerRoles::Editor->value,
    );

    $action = resolve(ChangeTeamMemberRoleAction::class);
    $action($organizer, $dto, $admin1);

    $organizer->refresh();
    $member = $organizer->users()->where('users.id', $admin2->id)->first();
    expect($member->pivot->role)->toBe(OrganizerRoles::Editor->value);
});
