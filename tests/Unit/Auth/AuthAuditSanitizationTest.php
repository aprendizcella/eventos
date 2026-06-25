<?php

declare(strict_types=1);

use App\Actions\Auth\RecordAuthActivityAction;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Sensitive keys that MUST NEVER survive audit sanitization, regardless of
 * casing or naming variant. Each dataset entry pairs a sensitive key with a
 * value that would be a clear leak if persisted.
 */
it('strips sensitive keys from audit context', function (string $sensitiveKey, mixed $leakyValue): void {
    $sanitized = RecordAuthActivityAction::sanitize([
        $sensitiveKey => $leakyValue,
        'outcome' => 'success',
        'ip' => '127.0.0.1',
    ]);

    expect($sanitized)
        ->not->toHaveKey($sensitiveKey)
        ->and($sanitized)->toHaveKey('outcome')
        ->and($sanitized)->toHaveKey('ip');
})->with([
    'password' => ['password', 'plain-secret'],
    'password_confirmation' => ['password_confirmation', 'plain-secret'],
    'current_password' => ['current_password', 'plain-secret'],
    'token' => ['token', 'reset-token-value'],
    'secret' => ['secret', 'leak'],
    'api_token' => ['api_token', 'api-leak'],
    'authorization' => ['authorization', 'Bearer leak'],
    'access_token' => ['access_token', 'access-leak'],
    'refresh_token' => ['refresh_token', 'refresh-leak'],
    'private_key' => ['private_key', '-----BEGIN PRIVATE KEY-----'],
]);

it('keeps only allowlisted privacy-safe properties and drops everything else', function (): void {
    $sanitized = RecordAuthActivityAction::sanitize([
        'outcome' => 'success',
        'ip' => '192.168.1.10',
        'user_agent' => 'Mozilla/5.0',
        'random_unrelated_field' => 'dropped',
        'another_unknown' => 'dropped',
    ]);

    expect($sanitized)->toBe([
        'outcome' => 'success',
        'ip' => '192.168.1.10',
        'user_agent' => 'Mozilla/5.0',
    ]);
});

it('returns an empty array when context contains only sensitive or unknown keys', function (): void {
    $sanitized = RecordAuthActivityAction::sanitize([
        'password' => 'plain-secret',
        'token' => 'reset-token',
        'unknown_field' => 'dropped',
    ]);

    expect($sanitized)->toBe([]);
});
