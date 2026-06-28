<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates the category table with correct columns', function (): void {
    expect(Schema::hasTable('category'))->toBeTrue();

    expect(Schema::hasColumns('category', [
        'category_id',
        'parent_id',
        'name',
        'slug',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue();
});

it('category table has unique index on name', function (): void {
    $indexes = Schema::getIndexes('category');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);

    expect($indexNames)->toContain('category_name_unique');
});

it('category table has foreign key on parent_id', function (): void {
    $foreignKeys = Schema::getForeignKeys('category');
    $columnNames = array_map(fn ($fk) => $fk['columns'] ?? [], $foreignKeys);
    $hasParentFk = array_any($columnNames, fn ($cols) => in_array('parent_id', $cols, true));

    expect($hasParentFk)->toBeTrue();
});
