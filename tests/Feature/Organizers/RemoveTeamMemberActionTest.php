<?php

declare(strict_types=1);

use App\Actions\Organizers\RemoveTeamMemberAction;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
});

it('removes a user from an organizer', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $adminRole = Role::query()->where('name', 'admin')->first();

    $organizer->users()->attach($admin1->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($admin2->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($user->id, ['role_id' => $adminRole->id]);

    $action = resolve(RemoveTeamMemberAction::class);
    $action($organizer, $user, $admin1);

    $organizer->refresh();
    expect($organizer->users)->toHaveCount(2)
        ->and($organizer->users->pluck('id'))->not->toContain($user->id);
});

it('logs activity when removing team member', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $user = User::factory()->create();
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $adminRole = Role::query()->where('name', 'admin')->first();

    $organizer->users()->attach($admin1->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($admin2->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($user->id, ['role_id' => $adminRole->id]);

    $action = resolve(RemoveTeamMemberAction::class);
    $action($organizer, $user, $admin1);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Organizer::class)
        ->where('subject_id', $organizer->id)
        ->where('description', 'team_member_removed')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['user_id'])->toBe($user->id);
});

it('prevents removing last admin', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin = User::factory()->create();
    $adminRole = Role::query()->where('name', 'admin')->first();

    $organizer->users()->attach($admin->id, ['role_id' => $adminRole->id]);

    $action = resolve(RemoveTeamMemberAction::class);

    expect(fn () => $action($organizer, $admin, $admin))
        ->toThrow(App\Exceptions\LastAdminCannotBeRemovedException::class);
});

it('allows removing admin if other admins exist', function (): void {
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $adminRole = Role::query()->where('name', 'admin')->first();

    $organizer->users()->attach($admin1->id, ['role_id' => $adminRole->id]);
    $organizer->users()->attach($admin2->id, ['role_id' => $adminRole->id]);

    $action = resolve(RemoveTeamMemberAction::class);
    $action($organizer, $admin2, $admin1);

    $organizer->refresh();
    expect($organizer->users)->toHaveCount(1)
        ->and($organizer->users->first()->id)->toBe($admin1->id);
});
