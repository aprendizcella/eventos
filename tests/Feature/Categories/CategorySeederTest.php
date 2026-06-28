<?php

declare(strict_types=1);

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('seeds the default category taxonomy', function (): void {
    $this->seed(CategorySeeder::class);

    expect(Category::query()->whereNull('parent_id')->count())->toBe(4)
        ->and(Category::query()->count())->toBe(12)
        ->and(Category::query()->where('slug', 'music')->first()?->parent_id)->not->toBeNull();
});

it('is idempotent', function (): void {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::query()->count())->toBe(12);
});
