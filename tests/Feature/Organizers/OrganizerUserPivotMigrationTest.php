<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates the organizer_user pivot table with correct columns', function (): void {
    $this->assertTrue(Schema::hasTable('organizer_user'));

    $this->assertTrue(Schema::hasColumns('organizer_user', [
        'organizer_id',
        'user_id',
        'role',
        'created_at',
        'updated_at',
    ]));
});

it('organizer_user table has foreign key constraints', function (): void {
    $foreignKeys = Schema::getForeignKeys('organizer_user');

    expect($foreignKeys)->toHaveCount(2);

    $fkColumns = array_map(fn ($fk) => $fk['foreign_table'], $foreignKeys);

    expect($fkColumns)->toContain('organizers')
        ->and($fkColumns)->toContain('users');
});

it('organizer_user table has unique composite index', function (): void {
    $indexes = Schema::getIndexes('organizer_user');

    $compositeIndex = collect($indexes)->first(
        fn ($idx) => count($idx['columns']) === 2
            && in_array('organizer_id', $idx['columns'], true)
            && in_array('user_id', $idx['columns'], true),
    );

    expect($compositeIndex)->not->toBeNull()
        ->and($compositeIndex['unique'])->toBeTrue();
});

it('organizer_user table has index on user_id', function (): void {
    $indexes = Schema::getIndexes('organizer_user');
    $indexNames = array_map(fn ($idx) => $idx['name'], $indexes);

    expect($indexNames)->toContain('organizer_user_user_id_index');
});

it('organizer_user table has index on organizer_id and role', function (): void {
    $indexes = Schema::getIndexes('organizer_user');

    $roleIndex = collect($indexes)->first(
        fn ($idx) => in_array('role', $idx['columns'], true) && in_array('organizer_id', $idx['columns'], true),
    );

    expect($roleIndex)->not->toBeNull();
});
