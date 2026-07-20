<?php

declare(strict_types=1);

use App\Enums\EventStatus;

it('has the expected cases', function (): void {
    expect(EventStatus::cases())->toHaveCount(7)
        ->and(EventStatus::Draft->value)->toBe('draft')
        ->and(EventStatus::Configured->value)->toBe('configured')
        ->and(EventStatus::Published->value)->toBe('published')
        ->and(EventStatus::Paused->value)->toBe('paused')
        ->and(EventStatus::Completed->value)->toBe('completed')
        ->and(EventStatus::Cancelled->value)->toBe('cancelled')
        ->and(EventStatus::Suspended->value)->toBe('suspended');
});

it('is a string-backed enum', function (): void {
    expect(EventStatus::Draft)->toBeInstanceOf(EventStatus::class)
        ->and(EventStatus::Draft->value)->toBeString();
});
