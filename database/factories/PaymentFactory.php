<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\TicketOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_order_id' => TicketOrder::factory(),
            'provider_id' => 'pi_'.fake()->regexify('[a-zA-Z0-9]{24}'),
            'payment_method' => PaymentMethod::Stripe,
            'status' => PaymentStatus::Completed,
            'amount' => 100.00,
            'currency' => 'USD',
        ];
    }
}
