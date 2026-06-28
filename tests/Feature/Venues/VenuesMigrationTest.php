<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates the venue table with correct columns', function (): void {
    expect(Schema::hasTable('venue'))->toBeTrue();

    expect(Schema::hasColumns('venue', [
        'venue_id',
        'organizer_id',
        'name',
        'address',
        'city',
        'capacity',
        'description',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue();
});

it('venue table has index on organizer_id', function (): void {
    $indexes = Schema::getIndexes('venue');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);

    expect($indexNames)->toContain('venue_organizer_id_index');
});

it('venue table has foreign key on organizer_id', function (): void {
    $foreignKeys = Schema::getForeignKeys('venue');
    $columnNames = array_map(fn ($fk) => $fk['columns'] ?? [], $foreignKeys);
    $hasOrganizerFk = array_any($columnNames, fn ($cols) => in_array('organizer_id', $cols, true));

    expect($hasOrganizerFk)->toBeTrue();
});
