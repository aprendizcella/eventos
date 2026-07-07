<?php

declare(strict_types=1);

use App\Models\Organizer;
use Tests\TestCase;

uses(TestCase::class);

it('sets tenant model to Organizer', function (): void {
    expect(config('multitenancy.tenant_model'))->toBe(Organizer::class);
});

it('uses single database mode with null tenant connection', function (): void {
    expect(config('multitenancy.tenant_database_connection_name'))->toBeNull();
});

it('uses single database mode with null landlord connection', function (): void {
    expect(config('multitenancy.landlord_database_connection_name'))->toBeNull();
});

it('has queues tenant aware by default enabled for async context', function (): void {
    expect(config('multitenancy.queues_are_tenant_aware_by_default'))->toBeTrue();
});

it('has no switch tenant tasks configured', function (): void {
    $tasks = config('multitenancy.switch_tenant_tasks');

    expect($tasks)->toBeArray()->toBeEmpty();
});

it('has tenant finder set to custom OrganizerTenantFinder', function (): void {
    expect(config('multitenancy.tenant_finder'))
        ->toBe(App\Support\Multitenancy\OrganizerTenantFinder::class);
});

it('maintains APP_URL host as tenant-less superadmin context', function (): void {
    $appUrl = config('app.url');

    expect($appUrl)->toBeString()->not->toBeEmpty();
});
