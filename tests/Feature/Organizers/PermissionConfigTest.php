<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

it('enables teams feature in permission config', function (): void {
    $this->assertTrue(config('permission.teams'));
});

it('sets team_foreign_key to organizer_id', function (): void {
    expect(config('permission.column_names.team_foreign_key'))->toBe('organizer_id');
});

it('sets team model to Organizer class', function (): void {
    expect(config('permission.models.team'))->toBe('App\Models\Organizer');
});
