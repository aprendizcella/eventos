<?php

declare(strict_types=1);

use App\Http\Resources\Api\V1\Admin\PlatformSettingResource;
use App\Http\Resources\Api\V1\Admin\UserResource;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

test('UserResource structures user data correctly', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    $resource = new UserResource($user);
    $array = $resource->toArray(new Request);

    expect($array)->toHaveKey('id', $user->id)
        ->toHaveKey('name', $user->name)
        ->toHaveKey('email', $user->email)
        ->toHaveKey('is_suspended', true)
        ->toHaveKey('suspended_at');
});

test('PlatformSettingResource structures setting data correctly', function () {
    $setting = PlatformSetting::current();

    $resource = new PlatformSettingResource($setting);
    $array = $resource->toArray(new Request);

    expect($array)->toHaveKey('settings', [])
        ->toHaveKey('lock_version', 0)
        ->toHaveKey('updated_at');
});
