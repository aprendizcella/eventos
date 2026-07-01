<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'provider_id' => 're_'.fake()->regexify('[a-zA-Z0-9]{24}'),
            'idempotency_key' => Str::uuid()->toString(),
            'status' => 'completed',
            'amount' => 50.00,
            'reason' => 'Customer request',
        ];
    }
}
