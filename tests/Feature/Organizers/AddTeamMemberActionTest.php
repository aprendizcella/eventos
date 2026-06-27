<?php

declare(strict_types=1);

use App\Actions\Organizers\AddTeamMemberAction;
use App\DataTransferObjects\Organizers\AddTeamMemberDto;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('adds a user to an organizer with a role', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();

    $dto = new AddTeamMemberDto(
        userId: $user->id,
        role: OrganizerRoles::Editor->value,
    );

    $action = resolve(AddTeamMemberAction::class);
    $action($organizer, $dto, $admin);

    expect($organizer->users)->toHaveCount(1)
        ->and($organizer->users->first()->id)->toBe($user->id)
        ->and($organizer->users->first()->pivot->role)->toBe(OrganizerRoles::Editor->value);
});

it('logs activity when adding team member', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();

    $dto = new AddTeamMemberDto(
        userId: $user->id,
        role: OrganizerRoles::Admin->value,
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
        ->and($activity->properties['role'])->toBe(OrganizerRoles::Admin->value);
});

it('prevents adding same user twice', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin = User::factory()->create();

    $dto = new AddTeamMemberDto(
        userId: $user->id,
        role: OrganizerRoles::Editor->value,
    );

    $action = resolve(AddTeamMemberAction::class);
    $action($organizer, $dto, $admin);

    expect(fn () => $action($organizer, $dto, $admin))
        ->toThrow(Illuminate\Database\QueryException::class);
});
