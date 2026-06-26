<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('adds organizer_id column to roles table', function (): void {
    $this->assertTrue(Schema::hasColumn('roles', 'organizer_id'));
});

it('adds organizer_id column to model_has_roles table', function (): void {
    $this->assertTrue(Schema::hasColumn('model_has_roles', 'organizer_id'));
});

it('adds organizer_id column to model_has_permissions table', function (): void {
    $this->assertTrue(Schema::hasColumn('model_has_permissions', 'organizer_id'));
});

it('roles table has index on organizer_id', function (): void {
    $indexes = Schema::getIndexes('roles');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);
    
    expect($indexNames)->toContain('roles_team_foreign_key_index');
});

it('model_has_roles table has index on organizer_id', function (): void {
    $indexes = Schema::getIndexes('model_has_roles');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);
    
    expect($indexNames)->toContain('model_has_roles_team_foreign_key_index');
});

it('model_has_permissions table has index on organizer_id', function (): void {
    $indexes = Schema::getIndexes('model_has_permissions');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);
    
    expect($indexNames)->toContain('model_has_permissions_team_foreign_key_index');
});
