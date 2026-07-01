<?php

declare(strict_types=1);

use App\Enums\PromoCodeType;
use App\Models\PromoCode;
use App\Services\PriceCalculator;
use Tests\TestCase;

uses(TestCase::class);

it('calculates normal subtotal without discount', function (): void {
    $calculator = new PriceCalculator;
    $result = $calculator->calculate(12.50, 3);

    expect($result)->toBe([
        'subtotal' => 37.50,
        'discount' => 0.00,
        'total' => 37.50,
    ]);
});

it('calculates subtotal with percentage discount', function (): void {
    $calculator = new PriceCalculator;

    $promoCode = new PromoCode([
        'type' => PromoCodeType::Percentage,
        'value' => 15.00,
        'status' => 'active',
    ]);

    $result = $calculator->calculate(50.00, 2, $promoCode);

    expect($result)->toBe([
        'subtotal' => 100.00,
        'discount' => 15.00,
        'total' => 85.00,
    ]);
});

it('calculates subtotal with fixed discount', function (): void {
    $calculator = new PriceCalculator;

    $promoCode = new PromoCode([
        'type' => PromoCodeType::Fixed,
        'value' => 20.00,
        'status' => 'active',
    ]);

    $result = $calculator->calculate(45.00, 2, $promoCode);

    expect($result)->toBe([
        'subtotal' => 90.00,
        'discount' => 20.00,
        'total' => 70.00,
    ]);
});

it('caps the discount to the subtotal amount', function (): void {
    $calculator = new PriceCalculator;

    $promoCode = new PromoCode([
        'type' => PromoCodeType::Fixed,
        'value' => 150.00,
        'status' => 'active',
    ]);

    $result = $calculator->calculate(50.00, 2, $promoCode);

    expect($result)->toBe([
        'subtotal' => 100.00,
        'discount' => 100.00,
        'total' => 0.00,
    ]);
});

it('ignores inactive promo codes', function (): void {
    $calculator = new PriceCalculator;

    $promoCode = new PromoCode([
        'type' => PromoCodeType::Percentage,
        'value' => 50.00,
        'status' => 'inactive',
    ]);

    $result = $calculator->calculate(80.00, 1, $promoCode);

    expect($result)->toBe([
        'subtotal' => 80.00,
        'discount' => 0.00,
        'total' => 80.00,
    ]);
});
