<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => Organizer::factory(),
            'name' => fake()->company().' '.fake()->word(),
            'address' => fake()->streetAddress(),
            'city' => fake()->optional()->city(),
            'capacity' => fake()->optional()->numberBetween(50, 20000),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
