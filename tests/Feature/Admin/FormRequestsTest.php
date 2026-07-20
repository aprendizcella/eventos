<?php

declare(strict_types=1);

use App\Http\Requests\Api\V1\Admin\AssignGlobalRoleRequest;
use App\Http\Requests\Api\V1\Admin\SuspendEventRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePlatformSettingsRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('SuspendEventRequest validates reason', function () {
    $request = new SuspendEventRequest;

    $validator = Validator::make(['reason' => ''], $request->rules());
    expect($validator->fails())->toBeTrue();

    $validator = Validator::make(['reason' => 'Violation'], $request->rules());
    expect($validator->fails())->toBeFalse();
});

test('UpdatePlatformSettingsRequest validates lock_version and commission', function () {
    $request = new UpdatePlatformSettingsRequest;

    $validator = Validator::make(['commission' => []], $request->rules());
    expect($validator->fails())->toBeTrue(); // lock_version required

    $validator = Validator::make([
        'lock_version' => 1,
        'commission' => [
            'platform_fee_percentage' => -10,
        ],
    ], $request->rules());
    expect($validator->fails())->toBeTrue(); // min:0

    $validator = Validator::make([
        'lock_version' => 1,
        'commission' => [
            'platform_fee_percentage' => 500,
            'platform_fee_fixed' => 50,
        ],
    ], $request->rules());
    expect($validator->fails())->toBeFalse();
});

test('AssignGlobalRoleRequest validates role name', function () {
    $request = new AssignGlobalRoleRequest;

    $validator = Validator::make(['role' => 'invalid_role'], $request->rules());
    expect($validator->fails())->toBeTrue();

    $validator = Validator::make(['role' => 'super_admin'], $request->rules());
    expect($validator->fails())->toBeFalse();
});
