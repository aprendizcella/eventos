<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TicketOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketOrderItem>
 */
class TicketOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_order_id' => \App\Models\TicketOrder::factory(),
            'product_id' => \App\Models\Product::factory(),
            'product_price_id' => \App\Models\ProductPrice::factory(),
            'quantity' => 1,
            'price' => 100.00,
            'subtotal' => 100.00,
            'discount' => 0.00,
            'total' => 100.00,
        ];
    }
}
