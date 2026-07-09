<?php

declare(strict_types=1);

use App\DataTransferObjects\Organizers\BillingSettings;
use App\Services\Payments\CommissionCalculator;
use Tests\TestCase;

uses(TestCase::class);

it('returns zero commission when organizer has no fee settings', function (): void {
    $calculator = new CommissionCalculator;
    $settings = BillingSettings::fromArray([]);

    $result = $calculator->calculate(10000, $settings, 'EUR');

    expect($result->grossAmount)->toBe(10000)
        ->and($result->percentageAmount)->toBe(0)
        ->and($result->fixedAmount)->toBe(0)
        ->and($result->commissionAmount)->toBe(0)
        ->and($result->netAmount)->toBe(10000)
        ->and($result->currency)->toBe('EUR');
});

it('calculates percentage and fixed commission from exact cents', function (): void {
    $calculator = new CommissionCalculator;
    $settings = BillingSettings::fromArray([
        'platform_fee_percentage' => 500,
        'platform_fee_fixed' => 99,
    ]);

    $result = $calculator->calculate(2000, $settings);

    expect($result->percentageAmount)->toBe(100)
        ->and($result->fixedAmount)->toBe(99)
        ->and($result->commissionAmount)->toBe(199)
        ->and($result->netAmount)->toBe(1801);
});

it('calculates only percentage commission when no fixed fee is set', function (): void {
    $calculator = new CommissionCalculator;
    $settings = BillingSettings::fromArray([
        'platform_fee_percentage' => 350,
    ]);

    $result = $calculator->calculate(5000, $settings);

    expect($result->percentageAmount)->toBe(175)
        ->and($result->fixedAmount)->toBe(0)
        ->and($result->commissionAmount)->toBe(175)
        ->and($result->netAmount)->toBe(4825);
});

it('caps commission at the gross amount', function (): void {
    $calculator = new CommissionCalculator;
    $settings = BillingSettings::fromArray([
        'platform_fee_percentage' => 9000,
        'platform_fee_fixed' => 5000,
    ]);

    $result = $calculator->calculate(1000, $settings);

    expect($result->commissionAmount)->toBe(1000)
        ->and($result->netAmount)->toBe(0);
});
