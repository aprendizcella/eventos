<?php

declare(strict_types=1);

use App\Models\Organizer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Multitenancy\Contracts\IsTenant;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('implements the IsTenant contract', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'Multitenant Organizer',
        'slug' => 'multitenant-organizer',
        'domain' => 'multitenant.example.com',
    ]);

    expect($organizer)->toBeInstanceOf(IsTenant::class);
});

it('can be made current tenant and forgotten', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'Current Tenant Test',
        'slug' => 'current-tenant-test',
    ]);

    expect(Organizer::checkCurrent())->toBeFalse();

    $organizer->makeCurrent();

    expect(Organizer::checkCurrent())->toBeTrue();
    expect(Organizer::current())->toBeInstanceOf(Organizer::class);
    expect(Organizer::current()?->getKey())->toBe($organizer->getKey());

    Organizer::forgetCurrent();

    expect(Organizer::checkCurrent())->toBeFalse();
});

it('reports isCurrent correctly', function (): void {
    $organizerA = Organizer::query()->create(['name' => 'A', 'slug' => 'a']);
    $organizerB = Organizer::query()->create(['name' => 'B', 'slug' => 'b']);

    expect($organizerA->isCurrent())->toBeFalse();
    expect($organizerB->isCurrent())->toBeFalse();

    $organizerA->makeCurrent();

    expect($organizerA->isCurrent())->toBeTrue();
    expect($organizerB->isCurrent())->toBeFalse();

    $organizerB->makeCurrent();

    expect($organizerA->isCurrent())->toBeFalse();
    expect($organizerB->isCurrent())->toBeTrue();

    Organizer::forgetCurrent();

    expect($organizerA->isCurrent())->toBeFalse();
    expect($organizerB->isCurrent())->toBeFalse();
});

it('returns the default connection database name', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'DB Name Test',
        'slug' => 'db-name-test',
    ]);

    $databaseName = $organizer->getDatabaseName();
    $defaultConnection = config('database.default');
    $expectedDatabase = config("database.connections.{$defaultConnection}.database");

    expect($databaseName)->toBe($expectedDatabase);
});

it('provides execute and callback for scoped operations', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'Execute Test',
        'slug' => 'execute-test',
    ]);

    $result = $organizer->execute(function (Organizer $tenant): string {
        expect($tenant->isCurrent())->toBeTrue();

        return 'executed';
    });

    expect($result)->toBe('executed');
    expect(Organizer::checkCurrent())->toBeFalse();
});
