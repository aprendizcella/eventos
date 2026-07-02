<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CheckInList;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckInList>
 */
final class CheckInListFactory extends Factory
{
    protected $model = CheckInList::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
