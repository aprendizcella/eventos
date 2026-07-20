<?php

declare(strict_types=1);

use App\Actions\Payments\CreatePayoutAction;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

test('it uses organizer commission settings if present', function () {
    $organizer = Organizer::factory()->create([
        'settings' => [
            'billing' => [
                'platform_fee_percentage' => 100, // 1%
                'platform_fee_fixed' => 10,
            ],
        ],
    ]);

    // Set platform setting to something else
    PlatformSetting::current()->update([
        'settings' => [
            'commission' => [
                'platform_fee_percentage' => 300,
                'platform_fee_fixed' => 30,
            ],
        ],
    ]);

    $invoice = Invoice::factory()->create(['organizer_id' => $organizer->id]);

    $action = resolve(CreatePayoutAction::class);
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('resolveBillingSettings');

    $settings = $method->invoke($action, $invoice);

    expect($settings->platformFeePercentage)->toBe(100)
        ->and($settings->platformFeeFixed)->toBe(10);
});

test('it falls back to platform settings if organizer has null commission', function () {
    $organizer = Organizer::factory()->create([
        'settings' => [
            'billing' => [
                'invoice_enabled' => true,
            ],
        ],
    ]);

    PlatformSetting::current()->update([
        'settings' => [
            'commission' => [
                'platform_fee_percentage' => 250,
                'platform_fee_fixed' => 25,
            ],
        ],
    ]);

    $invoice = Invoice::factory()->create(['organizer_id' => $organizer->id]);

    $action = resolve(CreatePayoutAction::class);
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('resolveBillingSettings');

    $settings = $method->invoke($action, $invoice);

    expect($settings->platformFeePercentage)->toBe(250)
        ->and($settings->platformFeeFixed)->toBe(25);
});

test('it honors explicit zero in platform settings', function () {
    $organizer = Organizer::factory()->create(['settings' => []]);

    PlatformSetting::current()->update([
        'settings' => [
            'commission' => [
                'platform_fee_percentage' => 0,
                'platform_fee_fixed' => 0,
            ],
        ],
    ]);

    $invoice = Invoice::factory()->create(['organizer_id' => $organizer->id]);

    $action = resolve(CreatePayoutAction::class);
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('resolveBillingSettings');

    $settings = $method->invoke($action, $invoice);

    expect($settings->platformFeePercentage)->toBe(0)
        ->and($settings->platformFeeFixed)->toBe(0);
});

test('it falls back to hardcoded defaults if both organizer and platform settings are null', function () {
    $organizer = Organizer::factory()->create(['settings' => []]);
    PlatformSetting::current()->update(['settings' => []]);

    config(['tickets.commission_default.platform_fee_percentage' => 500]);
    config(['tickets.commission_default.platform_fee_fixed' => 50]);

    $invoice = Invoice::factory()->create(['organizer_id' => $organizer->id]);

    $action = resolve(CreatePayoutAction::class);
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('resolveBillingSettings');

    $settings = $method->invoke($action, $invoice);

    expect($settings->platformFeePercentage)->toBe(500)
        ->and($settings->platformFeeFixed)->toBe(50);
});

test('historical payouts remain immutable when platform commission settings change', function () {
    $payout = App\Models\Payout::factory()->create([
        'commission_amount' => 500,
        'gross_amount' => 10000,
        'net_amount' => 9500,
    ]);

    // Change platform settings
    PlatformSetting::current()->update([
        'settings' => [
            'commission' => [
                'platform_fee_percentage' => 9900,
                'platform_fee_fixed' => 99,
            ],
        ],
    ]);

    // The historical payout commission amount should remain unchanged
    expect($payout->fresh()->commission_amount)->toBe(500);
});
