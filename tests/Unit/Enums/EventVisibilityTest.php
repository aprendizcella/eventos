<?php

declare(strict_types=1);

use App\Enums\EventVisibility;

it('has the expected cases', function (): void {
    expect(EventVisibility::cases())->toHaveCount(3)
        ->and(EventVisibility::Private->value)->toBe('private')
        ->and(EventVisibility::Public->value)->toBe('public')
        ->and(EventVisibility::PasswordProtected->value)->toBe('password_protected');
});

it('is a string-backed enum', function (): void {
    expect(EventVisibility::Private)->toBeInstanceOf(EventVisibility::class)
        ->and(EventVisibility::Private->value)->toBeString();
});
