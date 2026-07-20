<?php

declare(strict_types=1);

use App\Actions\Admin\Users\AssignGlobalRoleAction;
use App\Actions\Admin\Users\RestoreUserAction;
use App\Actions\Admin\Users\SendPasswordResetAction;
use App\Actions\Admin\Users\SuspendUserAction;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function () {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
});

test('it suspends an active user and revokes their tokens', function () {
    $user = User::factory()->create();
    $user->createToken('test-token');

    $action = resolve(SuspendUserAction::class);
    $result = $action($user);

    expect($result->isSuspended())->toBeTrue()
        ->and($result->suspended_at)->not->toBeNull()
        ->and($user->tokens()->count())->toBe(0);
});

test('it prevents suspending the last active super admin', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);

    $action = resolve(SuspendUserAction::class);

    expect(fn () => $action($superAdmin))->toThrow(ValidationException::class);
});

test('it allows suspending a super admin if another active super admin exists', function () {
    $superAdmin1 = User::factory()->create();
    $superAdmin2 = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin1->assignRole($role);
    $superAdmin2->assignRole($role);

    $action = resolve(SuspendUserAction::class);
    $action($superAdmin1);

    expect($superAdmin1->fresh()->isSuspended())->toBeTrue();
});

test('it restores a suspended user', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $action = resolve(RestoreUserAction::class);
    $result = $action($user);

    expect($result->isSuspended())->toBeFalse()
        ->and($result->suspended_at)->toBeNull();
});

test('it sends a password reset link notification to the user', function () {
    Notification::fake();
    $user = User::factory()->create();

    $action = resolve(SendPasswordResetAction::class);
    $action($user);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('it assigns a global role to a user if executor is super_admin', function () {
    $executor = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $executor->assignRole($role);

    $target = User::factory()->create();

    $action = resolve(AssignGlobalRoleAction::class);
    $action($executor, $target, 'platform_admin');

    expect($target->hasRole('platform_admin'))->toBeTrue();
});

test('it prevents assigning a global role if executor is not super_admin', function () {
    $executor = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $executor->assignRole($role);

    $target = User::factory()->create();

    $action = resolve(AssignGlobalRoleAction::class);

    expect(fn () => $action($executor, $target, 'super_admin'))->toThrow(Illuminate\Auth\Access\AuthorizationException::class);
});

use App\Actions\Admin\Users\RevokeGlobalRoleAction;

test('it revokes a global role if executor is super_admin', function () {
    $executor = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $executor->assignRole($role);

    $target = User::factory()->create();
    $targetRole = Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $target->assignRole($targetRole);

    $action = resolve(RevokeGlobalRoleAction::class);
    $action($executor, $target, 'platform_admin');

    expect($target->hasRole('platform_admin'))->toBeFalse();
});

test('it invalidates web sessions when suspending a user', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => Str::random(40),
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'TestBrowser',
        'payload' => 'dummy',
        'last_activity' => now()->timestamp,
    ]);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(1);

    $action = resolve(SuspendUserAction::class);
    $action($user);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});
