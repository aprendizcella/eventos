<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('defines the catalog indexes required for filtered queries', function () {
    expect(collect(Schema::getIndexes('event'))->pluck('name')->all())
        ->toContain('idx_event_category_id', 'idx_event_venue_id', 'idx_event_starts_at', 'idx_event_ends_at');

    expect(collect(Schema::getIndexes('category'))->pluck('name')->all())
        ->toContain('idx_category_parent_id', 'idx_category_slug');

    expect(collect(Schema::getIndexes('venue'))->pluck('name')->all())
        ->toContain('idx_venue_city');
});
