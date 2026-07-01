<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => \App\Models\Event::factory(),
            'code' => \Illuminate\Support\Str::upper(fake()->unique()->word()).fake()->numberBetween(10, 99),
            'type' => \App\Enums\PromoCodeType::Percentage,
            'value' => fake()->randomFloat(2, 5, 50),
            'max_uses' => fake()->optional()->numberBetween(10, 100),
            'uses_count' => 0,
            'start_at' => null,
            'end_at' => null,
            'status' => 'active',
        ];
    }
}
