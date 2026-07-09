<?php

declare(strict_types=1);

use App\Enums\PayoutStatus;
use Tests\TestCase;

uses(TestCase::class);

it('exposes the expected payout status values', function (): void {
    expect(PayoutStatus::Pending->value)->toBe('pending')
        ->and(PayoutStatus::Ready->value)->toBe('ready')
        ->and(PayoutStatus::Processed->value)->toBe('processed')
        ->and(PayoutStatus::Reversed->value)->toBe('reversed')
        ->and(PayoutStatus::Failed->value)->toBe('failed');
});
