<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates an organizer with valid attributes', function (): void {
    $organizer = Organizer::create([
        'name' => 'Test Organizer',
        'slug' => 'test-organizer',
        'domain' => 'test.example.com',
        'settings' => ['theme' => 'dark'],
        'status' => 'active',
    ]);

    expect($organizer)->toBeInstanceOf(Organizer::class)
        ->and($organizer->name)->toBe('Test Organizer')
        ->and($organizer->slug)->toBe('test-organizer')
        ->and($organizer->domain)->toBe('test.example.com')
        ->and($organizer->settings)->toBe(['theme' => 'dark'])
        ->and($organizer->status)->toBe('active');
});

it('casts settings to array', function (): void {
    $organizer = Organizer::create([
        'name' => 'Test',
        'slug' => 'test',
        'settings' => ['key' => 'value'],
    ]);

    $organizer->refresh();

    expect($organizer->settings)->toBeArray()
        ->and($organizer->settings)->toBe(['key' => 'value']);
});

it('has users relationship', function (): void {
    $organizer = Organizer::create([
        'name' => 'Test',
        'slug' => 'test',
    ]);

    $user = User::factory()->create();
    $role = \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $organizer->users()->attach($user->id, ['role_id' => $role->id]);

    expect($organizer->users)->toHaveCount(1)
        ->and($organizer->users->first()->id)->toBe($user->id);
});

it('has active scope', function (): void {
    Organizer::create(['name' => 'Active', 'slug' => 'active', 'status' => 'active']);
    Organizer::create(['name' => 'Inactive', 'slug' => 'inactive', 'status' => 'inactive']);

    $activeOrganizers = Organizer::active()->get();

    expect($activeOrganizers)->toHaveCount(1)
        ->and($activeOrganizers->first()->name)->toBe('Active');
});

it('has withDomain scope', function (): void {
    Organizer::create(['name' => 'With Domain', 'slug' => 'with-domain', 'domain' => 'example.com']);
    Organizer::create(['name' => 'No Domain', 'slug' => 'no-domain']);

    $withDomain = Organizer::withDomain()->get();

    expect($withDomain)->toHaveCount(1)
        ->and($withDomain->first()->name)->toBe('With Domain');
});

it('uses soft deletes', function (): void {
    $organizer = Organizer::create([
        'name' => 'Test',
        'slug' => 'test',
    ]);

    $organizer->delete();

    expect(Organizer::count())->toBe(0)
        ->and(Organizer::withTrashed()->count())->toBe(1);
});
