<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TicketOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketOrder>
 */
class TicketOrderFactory extends Factory
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
            'promo_code_id' => null,
            'order_reference' => 'ORD-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
            'status' => \App\Enums\TicketOrderStatus::Reserved,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'subtotal' => 100.00,
            'discount' => 0.00,
            'total' => 100.00,
            'reserved_until' => now()->addMinutes(10),
        ];
    }
}
