<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Tests\TestCase;

uses(TestCase::class);

it('implements non-blocking email-verification readiness on the user model', function (): void {
    expect(User::class)
        ->toImplement(MustVerifyEmail::class);
});

it('uses the approved sprint-1.1 package traits on the user model', function (): void {
    $traits = class_uses_recursive(User::class);

    expect($traits)
        ->toHaveKey(HasApiTokens::class)
        ->toHaveKey(HasRoles::class)
        ->toHaveKey(LogsActivity::class);
});

it('configures a privacy-safe activity log allowlist that excludes credentials and tokens', function (): void {
    $options = (new User)->getActivitylogOptions();

    expect($options)->toBeInstanceOf(LogOptions::class);

    $logAttributes = $options->logAttributes;

    expect($logAttributes)
        ->toContain('name')
        ->toContain('email')
        ->not->toContain('password')
        ->not->toContain('remember_token');
});
