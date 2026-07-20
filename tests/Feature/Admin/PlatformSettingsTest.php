<?php

declare(strict_types=1);

use App\Actions\Admin\PlatformSettings\UpdatePlatformSettingsAction;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

test('it updates platform settings with valid data and increments lock version', function () {
    $admin = User::factory()->create();
    $setting = PlatformSetting::current();
    $initialVersion = $setting->lock_version;

    $action = resolve(UpdatePlatformSettingsAction::class);
    $result = $action([
        'commission' => [
            'platform_fee_percentage' => 500,
            'platform_fee_fixed' => 50,
        ],
    ], $initialVersion, $admin);

    expect($result->lock_version)->toBe($initialVersion + 1)
        ->and($result->setting('commission')['platform_fee_percentage'])->toBe(500);
});

test('it rejects concurrent updates with stale lock version', function () {
    $admin = User::factory()->create();
    $setting = PlatformSetting::current();

    // Simulate another process updating it
    $setting->increment('lock_version');

    $action = resolve(UpdatePlatformSettingsAction::class);

    expect(fn () => $action([
        'commission' => [
            'platform_fee_percentage' => 500,
            'platform_fee_fixed' => 50,
        ],
    ], $setting->lock_version - 1, $admin))->toThrow(ValidationException::class);
});

it('prevents concurrent updates to platform settings', function () {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);

    $settings = PlatformSetting::current();

    $action = resolve(UpdatePlatformSettingsAction::class);

    $actor = User::factory()->create();
    $role = Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $actor->assignRole($role);

    // First update succeeds
    $action(['app_name' => 'New Name'], $settings->lock_version, $actor);

    // Second update fails with same lock_version
    expect(fn () => $action(['app_name' => 'Failed Name'], $settings->lock_version, $actor))
        ->toThrow(ValidationException::class);
});
