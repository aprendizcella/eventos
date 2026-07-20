<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureGlobalAdminContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('forces team_id to 0 and restores the previous team_id after execution', function () {
    setPermissionsTeamId(5);

    $middleware = new EnsureGlobalAdminContext;
    $request = Request::create('/admin/test', 'GET');

    $executed = false;

    $response = $middleware->handle($request, function ($req) use (&$executed) {
        $executed = true;
        expect(getPermissionsTeamId())->toBe(0);

        return new Response;
    });

    expect($executed)->toBeTrue();
    $middleware->terminate($request, $response);
    expect(getPermissionsTeamId())->toBe(5);
});

it('allows assigning a role specifically to team 0 and verifying it', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'super_admin', 'organizer_id' => 0]);

    setPermissionsTeamId(0);
    $user->assignRole('super_admin');

    expect($user->hasRole('super_admin'))->toBeTrue();

    setPermissionsTeamId(1);
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');

    expect($user->hasRole('super_admin'))->toBeFalse();
});
