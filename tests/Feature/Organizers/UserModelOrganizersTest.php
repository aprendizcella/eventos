<?php

declare(strict_types=1);

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('has organizers relationship', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $role = Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $user->organizers()->attach($organizer->id, ['role_id' => $role->id]);

    expect($user->organizers)->toHaveCount(1)
        ->and($user->organizers->first()->id)->toBe($organizer->id);
});

it('can belong to multiple organizers', function (): void {
    $user = User::factory()->create();
    $organizer1 = Organizer::create(['name' => 'Org 1', 'slug' => 'org-1']);
    $organizer2 = Organizer::create(['name' => 'Org 2', 'slug' => 'org-2']);
    $role = Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $user->organizers()->attach([
        $organizer1->id => ['role_id' => $role->id],
        $organizer2->id => ['role_id' => $role->id],
    ]);

    expect($user->organizers)->toHaveCount(2);
});

it('resolves currentOrganizer from request attribute', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $role = Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $user->organizers()->attach($organizer->id, ['role_id' => $role->id]);

    $request = Request::create('/test');
    $request->attributes->set('current_organizer', $organizer);

    app()->instance('request', $request);

    expect($user->currentOrganizer())->toBeInstanceOf(Organizer::class)
        ->and($user->currentOrganizer()->id)->toBe($organizer->id);
});

it('resolves currentOrganizer from session', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::create(['name' => 'Test', 'slug' => 'test']);
    $role = Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $user->organizers()->attach($organizer->id, ['role_id' => $role->id]);

    $request = Request::create('/test');
    $request->setLaravelSession(app('session.store'));
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
