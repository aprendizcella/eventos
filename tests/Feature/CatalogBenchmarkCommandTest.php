<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\artisan;

uses(TestCase::class, RefreshDatabase::class);

it('runs benchmark command successfully', function () {
    artisan('catalog:benchmark', ['count' => 5])
        ->expectsOutput('Seeding 5 events for benchmark...')
        ->expectsOutput('Starting benchmark...')
        ->expectsOutputToContain('First call (uncached or warm):')
        ->expectsOutputToContain('Second call (cached):')
        ->assertSuccessful();
});

it('fails if count is 0', function () {
    artisan('catalog:benchmark', ['count' => 0])
        ->expectsOutput('Count must be greater than zero.')
        ->assertFailed();
});
