<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('redirects guest to login when accessing profile page', function (): void {
    $this->get(route('account.profile.edit'))
        ->assertRedirect(route('login'));
});

it('redirects guest to login when accessing password page', function (): void {
    $this->get(route('account.password.edit'))
        ->assertRedirect(route('login'));
});

it('renders profile page for authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee($user->email);
});

it('renders password page for authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('account.password.edit'))
        ->assertOk()
        ->assertSee('Current Password')
        ->assertSee('New Password');
});
