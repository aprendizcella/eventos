<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('has organizers relationship', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $user->organizers()->attach($organizer->id, ['role' => OrganizerRoles::Admin->value]);

    expect($user->organizers)->toHaveCount(1)
        ->and($user->organizers->first()->id)->toBe($organizer->id);
});

it('can belong to multiple organizers', function (): void {
    $user = User::factory()->create();
    $organizer1 = Organizer::query()->create(['name' => 'Org 1', 'slug' => 'org-1']);
    $organizer2 = Organizer::query()->create(['name' => 'Org 2', 'slug' => 'org-2']);

    $user->organizers()->attach([
        $organizer1->id => ['role' => OrganizerRoles::Admin->value],
        $organizer2->id => ['role' => OrganizerRoles::Admin->value],
    ]);

    expect($user->organizers)->toHaveCount(2);
});

it('resolves currentOrganizer from request attribute', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $user->organizers()->attach($organizer->id, ['role' => OrganizerRoles::Admin->value]);

    $request = Request::create('/test');
    $request->attributes->set('current_organizer', $organizer);

    app()->instance('request', $request);

    expect($user->currentOrganizer())->toBeInstanceOf(Organizer::class)
        ->and($user->currentOrganizer()->id)->toBe($organizer->id);
});

it('resolves currentOrganizer from session', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::query()->create(['name' => 'Test', 'slug' => 'test']);

    $user->organizers()->attach($organizer->id, ['role' => OrganizerRoles::Admin->value]);

    $request = Request::create('/test');
    $request->setLaravelSession(resolve('session.store'));
    session()->put('current_organizer_id', $organizer->id);

    app()->instance('request', $request);

    expect($user->currentOrganizer())->toBeInstanceOf(Organizer::class)
        ->and($user->currentOrganizer()->id)->toBe($organizer->id);
});

it('returns null when no current organizer is set', function (): void {
    $user = User::factory()->create();

    $request = Request::create('/test');
    app()->instance('request', $request);

    expect($user->currentOrganizer())->toBeNull();
});
