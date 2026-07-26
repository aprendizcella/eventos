<?php

declare(strict_types=1);

use App\Services\Webhooks\UnsafeWebhookDestinationException;
use App\Services\Webhooks\WebhookDestinationPolicy;

it('accepts HTTPS hostnames that resolve exclusively to public addresses', function (): void {
    $policy = new WebhookDestinationPolicy;

    $policy->assertSafe('https://hooks.example.com/events', fn (string $host): array => [
        '93.184.216.34',
        '2001:4860:4860::8888',
    ]);

    expect(true)->toBeTrue();
});

it('rejects private, loopback, link-local, multicast, and reserved addresses', function (string $address): void {
    $policy = new WebhookDestinationPolicy;

    expect(fn (): mixed => $policy->assertSafe(
        'https://hooks.example.com/events',
        fn (string $host): array => [$address],
    ))->toThrow(UnsafeWebhookDestinationException::class);
})->with([
    '10.0.0.1',
    '127.0.0.1',
    '169.254.1.1',
    '224.0.0.1',
    '192.0.2.1',
    'fc00::1',
    '::1',
    'fe80::1',
    'ff02::1',
]);

it('rejects DNS results containing a public and private address together', function (): void {
    $policy = new WebhookDestinationPolicy;

    expect(fn (): mixed => $policy->assertSafe(
        'https://hooks.example.com/events',
        fn (string $host): array => ['93.184.216.34', '10.0.0.1'],
    ))->toThrow(UnsafeWebhookDestinationException::class);
});

it('rejects credentials, IP literals, non-HTTPS, and non-443 destinations', function (string $endpoint): void {
    $policy = new WebhookDestinationPolicy;

    expect(fn (): mixed => $policy->assertSafe($endpoint, fn (string $host): array => ['93.184.216.34']))
        ->toThrow(UnsafeWebhookDestinationException::class);
})->with([
    'http://hooks.example.com/events',
    'https://hooks.example.com:8443/events',
    'https://user:pass@hooks.example.com/events',
    'https://127.0.0.1/events',
]);
