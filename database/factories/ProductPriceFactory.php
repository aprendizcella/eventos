<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'name' => fake()->randomElement(['General Admission', 'Early Bird', 'VIP']),
            'price' => fake()->randomFloat(2, 5, 200),
            'capacity' => fake()->optional()->numberBetween(50, 1000),
            'quantity_sold' => 0,
            'sales_start_at' => null,
            'sales_end_at' => null,
        ];
    }
}
