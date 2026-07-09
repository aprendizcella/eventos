<?php

declare(strict_types=1);

use App\DataTransferObjects\Organizers\BillingSettings;
use Tests\TestCase;

uses(TestCase::class);

it('creates billing settings with default values from empty array', function (): void {
    $settings = BillingSettings::fromArray([]);

    expect($settings->invoiceEnabled)->toBeFalse()
        ->and($settings->taxId)->toBeNull()
        ->and($settings->taxName)->toBeNull()
        ->and($settings->taxRate)->toBeNull()
        ->and($settings->platformFeePercentage)->toBeNull()
        ->and($settings->platformFeeFixed)->toBeNull();
});

it('creates billing settings from full array', function (): void {
    $settings = BillingSettings::fromArray([
        'invoice_enabled' => true,
        'tax_id' => 'TAX-123',
        'tax_name' => 'VAT',
        'tax_rate' => 2100,
        'platform_fee_percentage' => 350,
        'platform_fee_fixed' => 50,
    ]);

    expect($settings->invoiceEnabled)->toBeTrue()
        ->and($settings->taxId)->toBe('TAX-123')
        ->and($settings->taxName)->toBe('VAT')
        ->and($settings->taxRate)->toBe(2100)
        ->and($settings->platformFeePercentage)->toBe(350)
        ->and($settings->platformFeeFixed)->toBe(50);
});

it('serializes back to array excluding nulls', function (): void {
    $settings = BillingSettings::fromArray([
        'invoice_enabled' => true,
        'tax_id' => 'TAX-123',
    ]);

    $array = $settings->toArray();

    expect($array)->toHaveKey('invoice_enabled')
        ->toHaveKey('tax_id')
        ->not->toHaveKey('tax_name')
        ->not->toHaveKey('tax_rate')
        ->not->toHaveKey('platform_fee_percentage')
        ->not->toHaveKey('platform_fee_fixed');
});

it('merges billing settings into existing settings array', function (): void {
    $existing = ['theme' => 'dark', 'timezone' => 'UTC'];
    $settings = BillingSettings::fromArray([
        'invoice_enabled' => true,
        'tax_rate' => 2100,
    ]);

    $merged = $settings->mergeInto($existing);

    expect($merged)->toHaveKey('theme')
        ->toHaveKey('timezone')
        ->toHaveKey('invoice_enabled')
        ->toHaveKey('tax_rate')
        ->and($merged['theme'])->toBe('dark')
        ->and($merged['invoice_enabled'])->toBeTrue()
        ->and($merged['tax_rate'])->toBe(2100);
});

it('round-trips through array serialization correctly', function (): void {
    $original = BillingSettings::fromArray([
        'invoice_enabled' => true,
        'tax_id' => 'TAX-456',
        'tax_name' => 'GST',
        'tax_rate' => 1000,
        'platform_fee_percentage' => 200,
        'platform_fee_fixed' => 30,
    ]);

    $restored = BillingSettings::fromArray($original->toArray());

    expect($restored->invoiceEnabled)->toBe($original->invoiceEnabled)
        ->and($restored->taxId)->toBe($original->taxId)
        ->and($restored->taxName)->toBe($original->taxName)
        ->and($restored->taxRate)->toBe($original->taxRate)
        ->and($restored->platformFeePercentage)->toBe($original->platformFeePercentage)
        ->and($restored->platformFeeFixed)->toBe($original->platformFeeFixed);
});

it('casts invoice_enabled properly from various truthy values', function (): void {
    $settings = BillingSettings::fromArray(['invoice_enabled' => 1]);
    expect($settings->invoiceEnabled)->toBeTrue();

    $settings = BillingSettings::fromArray(['invoice_enabled' => '1']);
    expect($settings->invoiceEnabled)->toBeTrue();

    $settings = BillingSettings::fromArray(['invoice_enabled' => 'true']);
    expect($settings->invoiceEnabled)->toBeTrue();
});

it('treats missing invoice_enabled as false', function (): void {
    $settings = BillingSettings::fromArray([]);

    expect($settings->invoiceEnabled)->toBeFalse();
});
