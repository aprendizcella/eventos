<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WaitlistStatus;
use App\Models\Event;
use App\Models\ProductPrice;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
final class WaitlistEntryFactory extends Factory
{
    protected $model = WaitlistEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'product_price_id' => ProductPrice::factory(),
            'email' => fake()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => WaitlistStatus::Waiting,
        ];
    }
}
