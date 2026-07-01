<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'event_id' => \App\Models\Event::factory(),
            'organizer_id' => fn (array $attributes) => \App\Models\Event::query()->find($attributes['event_id'])->organizer_id,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'description' => fake()->paragraph,
            'type' => \App\Enums\ProductType::Ticket,
            'pricing_mode' => \App\Enums\PricingMode::Paid,
            'status' => \App\Enums\ProductStatus::Active,
            'visibility' => \App\Enums\ProductVisibility::Public,
            'password' => null,
            'min_qty' => 1,
            'max_qty' => 10,
            'sort_order' => 0,
        ];
    }
}
