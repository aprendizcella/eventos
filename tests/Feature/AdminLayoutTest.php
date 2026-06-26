<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('redirects unauthenticated users to login', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('renders the dashboard for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Total Events')
        ->assertSee('Active Organizers')
        ->assertSee('Total Tickets Sold');
});

it('includes the admin layout structure', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Sidebar navigation', false)
        ->assertSee('data-theme-toggle', false)
        ->assertSee('data-theme-option="light"', false)
        ->assertSee('data-theme-option="dark"', false)
        ->assertSee('data-theme-option="system"', false);
});

it('includes the theme initialization script', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('localStorage.getItem', false);
});
