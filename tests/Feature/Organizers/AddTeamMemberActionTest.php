<?php

declare(strict_types=1);

use App\Actions\Organizers\AddTeamMemberAction;
use App\DataTransferObjects\Organizers\AddTeamMemberDto;
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

it('adds a user to an organizer with a role', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $role = Role::query()->where('name', 'editor')->first();

    $dto = new AddTeamMemberDto(
        userId: $user->id,
        roleId: $role->id,
    );

    $action = resolve(AddTeamMemberAction::class);
    $action($organizer, $dto, $admin);

    expect($organizer->users)->toHaveCount(1)
        ->and($organizer->users->first()->id)->toBe($user->id)
        ->and($organizer->users->first()->pivot->role_id)->toBe($role->id);
});

it('logs activity when adding team member', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $role = Role::query()->where('name', 'admin')->first();

    $dto = new AddTeamMemberDto(
        userId: $user->id,
        roleId: $role->id,
    );

    $action = resolve(AddTeamMemberAction::class);
    $action($organizer, $dto, $admin);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_added')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['user_id'])->toBe($user->id)
        ->and($activity->properties['role_id'])->toBe($role->id);
});

it('prevents adding same user twice', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $role = Role::query()->where('name', 'editor')->first();

    $dto = new AddTeamMemberDto(
        userId: $user->id,
        roleId: $role->id,
    );

    $action = resolve(AddTeamMemberAction::class);
    $action($organizer, $dto, $admin);

    expect(fn () => $action($organizer, $dto, $admin))
        ->toThrow(Illuminate\Database\QueryException::class);
});
