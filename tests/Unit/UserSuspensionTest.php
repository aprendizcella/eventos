<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can check if user is suspended', function () {
    $user = User::factory()->create();

    expect($user->isSuspended())->toBeFalse();

    $user->suspended_at = now();
    $user->save();

    expect($user->isSuspended())->toBeTrue();
});

it('can filter out suspended users using the active scope', function () {
    User::factory()->create();
    User::factory()->create(['suspended_at' => now()]);

    expect(User::query()->active()->count())->toBe(1);
});
