<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => Organizer::factory(),
            'invoice_id' => Invoice::factory(),
            'refund_id' => null,
            'gross_amount' => 10000,
            'commission_amount' => 1200,
            'net_amount' => 8800,
            'currency' => 'USD',
            'status' => PayoutStatus::Pending,
            'processed_at' => null,
            'reversed_at' => null,
            'notes' => null,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (): array => [
            'status' => PayoutStatus::Ready,
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (): array => [
            'status' => PayoutStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function reversed(): static
    {
        return $this->state(fn (): array => [
            'status' => PayoutStatus::Reversed,
            'reversed_at' => now(),
        ]);
    }
}
