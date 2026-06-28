<?php

declare(strict_types=1);

use App\Models\Category;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates a category with required fields', function (): void {
    $category = Category::factory()->create(['name' => 'Música']);

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->name)->toBe('Música');
});

it('enforces unique name', function (): void {
    Category::factory()->create(['name' => 'Música']);

    expect(fn () => Category::factory()->create(['name' => 'Música']))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('root category has null parent', function (): void {
    $root = Category::factory()->create();

    expect($root->parent)->toBeNull();
});

it('children relationship returns child categories', function (): void {
    $root = Category::factory()->create(['name' => 'Música']);
    $child = Category::factory()->create(['name' => 'Electrónica', 'parent_id' => $root->category_id]);

    expect($root->children)->toHaveCount(1)
        ->and($root->children->first()->category_id)->toBe($child->category_id);
});

it('parent relationship returns parent category', function (): void {
    $root = Category::factory()->create(['name' => 'Música']);
    $child = Category::factory()->create(['name' => 'Electrónica', 'parent_id' => $root->category_id]);

    expect($child->parent)->not->toBeNull()
        ->and($child->parent->category_id)->toBe($root->category_id);
});

it('uses soft deletes', function (): void {
    $category = Category::factory()->create();
    $category->delete();

    expect(Category::query()->count())->toBe(0)
        ->and(Category::withTrashed()->count())->toBe(1);
});

it('factory produces valid category', function (): void {
    $category = Category::factory()->create();

    expect($category->category_id)->not->toBeNull()
        ->and($category->name)->not->toBeEmpty();
});
