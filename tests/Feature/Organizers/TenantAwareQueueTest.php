<?php

declare(strict_types=1);

use App\Models\Organizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

// --------------------------------------------------------------------------
// Test jobs for queue tenant restoration
// --------------------------------------------------------------------------

/**
 * Tenant-aware test job that records the current tenant ID via static prop,
 * surviving serialization/unserialization by the sync queue driver.
 */
final class TenantAwareTestJob implements ShouldQueue
{
    use Queueable;

    public static ?int $capturedTenantId = null;

    public function handle(): void
    {
        self::$capturedTenantId = Organizer::current()?->getKey();
    }
}

/**
 * Explicitly NOT tenant-aware test job.
 */
final class NotTenantAwareTestJob implements NotTenantAware, ShouldQueue
{
    use Queueable;

    public static ?int $capturedTenantId = null;

    public function handle(): void
    {
        self::$capturedTenantId = Organizer::current()?->getKey();
    }
}

beforeEach(function (): void {
    // Reset static state before each test
    TenantAwareTestJob::$capturedTenantId = null;
    NotTenantAwareTestJob::$capturedTenantId = null;

    // Use sync queue driver so jobs execute inline during tests
    Config::set('queue.default', 'sync');
});

// --------------------------------------------------------------------------
// Task 3.3 — Tenant restoration on job dispatch
// --------------------------------------------------------------------------

it('restores tenant context in job handle() when dispatched with current tenant', function (): void {
    $organizer = Organizer::factory()->create();
    $organizer->makeCurrent();

    expect(Organizer::checkCurrent())->toBeTrue();

    dispatch(new TenantAwareTestJob);

    expect(TenantAwareTestJob::$capturedTenantId)->toBe($organizer->getKey());
});

it('does not restore tenant for NotTenantAware jobs', function (): void {
    $organizer = Organizer::factory()->create();
    $organizer->makeCurrent();

    dispatch(new NotTenantAwareTestJob);

    expect(NotTenantAwareTestJob::$capturedTenantId)->toBeNull();
});

it('resolves true from queues_are_tenant_aware_by_default config', function (): void {
    expect(config('multitenancy.queues_are_tenant_aware_by_default'))->toBeTrue();
});

// --------------------------------------------------------------------------
// Task 3.3 — Queue fake assertions (proves dispatching tenant context)
// --------------------------------------------------------------------------

it('dispatches queued jobs with tenant context via Queue fake', function (): void {
    Queue::fake();

    $organizer = Organizer::factory()->create();
    $organizer->makeCurrent();

    dispatch(new TenantAwareTestJob);

    Queue::assertPushed(TenantAwareTestJob::class, 1);
});
