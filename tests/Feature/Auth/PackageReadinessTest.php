<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

uses(Tests\TestCase::class);

it('loads the approved auth support package configuration and classes', function (): void {
    expect(config('sanctum'))->toBeArray()
        ->and(config('permission'))->toBeArray()
        ->and(config('activitylog'))->toBeArray()
        ->and(config('purifier'))->toBeArray()
        ->and(config('livewire'))->toBeArray();

    expect(class_exists(Laravel\Sanctum\Sanctum::class))->toBeTrue()
        ->and(class_exists(Spatie\Permission\Models\Role::class))->toBeTrue()
        ->and(class_exists(Spatie\Activitylog\Models\Activity::class))->toBeTrue()
        ->and(class_exists(Mews\Purifier\Purifier::class))->toBeTrue()
        ->and(class_exists(Livewire\Livewire::class))->toBeTrue()
        ->and(class_exists(Livewire\Volt\Volt::class))->toBeTrue();
});

it('publishes package migrations needed by the auth foundation', function (string $migrationPattern): void {
    expect(glob(database_path($migrationPattern)))->not->toBeEmpty();
})->with([
    'sanctum personal access tokens' => 'migrations/*create_personal_access_tokens_table.php',
    'permission tables' => 'migrations/*create_permission_tables.php',
    'activity log table' => 'migrations/*create_activity_log_table.php',
]);

it('registers package middleware aliases and auth rate limiters', function (): void {
    $middlewareAliases = resolve('router')->getMiddleware();

    expect($middlewareAliases)->toHaveKeys([
        'role',
        'permission',
        'role_or_permission',
    ])
        ->and(RateLimiter::limiter('login'))->not->toBeNull()
        ->and(RateLimiter::limiter('password-reset-request'))->not->toBeNull();
});

it('does not expose an API token issuance endpoint', function (): void {
    $this->postJson('/api/tokens/create')->assertNotFound();
});
