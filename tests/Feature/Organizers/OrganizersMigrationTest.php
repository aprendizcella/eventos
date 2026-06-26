<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates the organizers table with correct columns', function (): void {
    $this->assertTrue(Schema::hasTable('organizers'));
    
    $this->assertTrue(Schema::hasColumns('organizers', [
        'id',
        'name',
        'slug',
        'domain',
        'settings',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ]));
});

it('organizers table has correct column types', function (): void {
    $columns = Schema::getColumns('organizers');
    
    $columnNames = array_map(fn ($col) => $col['name'], $columns);
    
    expect($columnNames)->toContain('id')
        ->and($columnNames)->toContain('name')
        ->and($columnNames)->toContain('slug')
        ->and($columnNames)->toContain('domain')
        ->and($columnNames)->toContain('settings')
        ->and($columnNames)->toContain('status')
        ->and($columnNames)->toContain('deleted_at');
});

it('organizers table has unique index on slug', function (): void {
    $indexes = Schema::getIndexes('organizers');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);
    
    expect($indexNames)->toContain('organizers_slug_unique');
});

it('organizers table has unique index on domain when not null', function (): void {
    $indexes = Schema::getIndexes('organizers');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);
    
    expect($indexNames)->toContain('organizers_domain_unique');
});

it('organizers table has index on status', function (): void {
    $indexes = Schema::getIndexes('organizers');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);
    
    expect($indexNames)->toContain('organizers_status_index');
});
