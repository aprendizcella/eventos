<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates the event table with correct columns', function (): void {
    expect(Schema::hasTable('event'))->toBeTrue();

    expect(Schema::hasColumns('event', [
        'event_id',
        'organizer_id',
        'category_id',
        'venue_id',
        'title',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'visibility',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue();
});

it('event table has unique index on slug', function (): void {
    $indexes = Schema::getIndexes('event');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);

    expect($indexNames)->toContain('event_slug_unique');
});

it('event table has index on organizer_id', function (): void {
    $indexes = Schema::getIndexes('event');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);

    expect($indexNames)->toContain('event_organizer_id_index');
});

it('event table has index on status', function (): void {
    $indexes = Schema::getIndexes('event');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);

    expect($indexNames)->toContain('event_status_index');
});

it('event table has foreign keys on organizer_id, category_id and venue_id', function (): void {
    $foreignKeys = Schema::getForeignKeys('event');
    $columnNames = array_map(fn ($fk) => $fk['columns'] ?? [], $foreignKeys);

    $expected = ['organizer_id', 'category_id', 'venue_id'];

    foreach ($expected as $column) {
        $found = array_any($columnNames, fn ($cols) => in_array($column, $cols, true));
        expect($found)->toBeTrue("Missing foreign key on {$column}");
    }
});
