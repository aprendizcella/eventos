<?php

declare(strict_types=1);

use App\Models\PlatformSetting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('enforces a single row via db unique constraint', function () {
    PlatformSetting::query()->create(['settings' => ['commission' => 5], 'is_singleton' => true]);

    expect(fn () => PlatformSetting::query()->create(['settings' => ['commission' => 10], 'is_singleton' => true]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('can get the current singleton and reads json settings', function () {
    $setting = PlatformSetting::current();
    expect($setting)->toBeInstanceOf(PlatformSetting::class);

    // Default lock version
    expect($setting->lock_version)->toBe(0);
});

it('can check if a key exists via setting()', function () {
    $setting = PlatformSetting::current();
    $setting->settings = ['maintenance' => true];
    $setting->save();

    expect($setting->setting('maintenance'))->toBeTrue();
    expect($setting->setting('missing_key'))->toBeNull();
});
