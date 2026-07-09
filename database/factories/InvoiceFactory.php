<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\TicketOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => Organizer::factory(),
            'ticket_order_id' => TicketOrder::factory(),
            'payment_id' => Payment::factory(),
            'refund_id' => null,
            'type' => InvoiceType::Invoice,
            'year' => now()->year,
            'number' => fake()->unique()->numberBetween(1, 9999),
            'invoice_number' => function (array $attrs): string {
                $type = $attrs['type'] ?? InvoiceType::Invoice;

                if (is_string($type)) {
                    /** @var InvoiceType $type */
                    $type = InvoiceType::from($type);
                }

                return sprintf(
                    '%s-%d-%04d',
                    $type->prefix(),
                    (int) ($attrs['year'] ?? now()->year),
                    (int) ($attrs['number'] ?? 1),
                );
            },
            'amount' => fake()->numberBetween(500, 50000),
            'tax_amount' => fake()->optional()->numberBetween(50, 5000),
            'fee_amount' => fake()->optional()->numberBetween(25, 2500),
            'currency' => 'USD',
            'status' => InvoiceStatus::Issued,
            'notes' => null,
        ];
    }

    public function creditNote(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => InvoiceType::CreditNote,
            'payment_id' => null,
        ]);
    }
}
