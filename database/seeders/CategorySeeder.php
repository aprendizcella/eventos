<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    /**
     * @var array<string, list<array{name: string, slug: string}>>
     */
    public const array TAXONOMY = [
        'arts-culture' => [
            ['name' => 'Music', 'slug' => 'music'],
            ['name' => 'Theatre', 'slug' => 'theatre'],
        ],
        'business' => [
            ['name' => 'Networking', 'slug' => 'networking'],
            ['name' => 'Conference', 'slug' => 'conference'],
        ],
        'community' => [
            ['name' => 'Charity', 'slug' => 'charity'],
            ['name' => 'Education', 'slug' => 'education'],
        ],
        'sports' => [
            ['name' => 'Fitness', 'slug' => 'fitness'],
            ['name' => 'Tournament', 'slug' => 'tournament'],
        ],
    ];

    public function run(): void
    {
        foreach (self::TAXONOMY as $parentSlug => $children) {
            $parent = Category::query()->firstOrCreate(
                ['slug' => $parentSlug],
                ['name' => str($parentSlug)->replace('-', ' ')->title()->toString()],
            );

            foreach ($children as $child) {
                Category::query()->firstOrCreate(
                    ['slug' => $child['slug']],
                    ['name' => $child['name'], 'parent_id' => $parent->category_id],
                );
            }
        }
    }
}
