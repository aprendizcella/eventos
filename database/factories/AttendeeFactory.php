<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttendeeStatus;
use App\Models\Attendee;
use App\Models\TicketOrder;
use App\Models\TicketOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attendee>
 */
final class AttendeeFactory extends Factory
{
    protected $model = Attendee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_order_id' => TicketOrder::factory(),
            'ticket_order_item_id' => TicketOrderItem::factory(),
            'sequence' => 1,
            'unique_code' => 'TKT-'.strtoupper(Str::random(8)),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'status' => AttendeeStatus::Active,
        ];
    }
}
