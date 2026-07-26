<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organizer = Organizer::factory();
        $webhook = Webhook::factory()->for($organizer);

        return [
            'delivery_id' => Str::uuid()->toString(),
            'organizer_id' => $organizer,
            'webhook_id' => $webhook,
            'payment_id' => Payment::factory(),
            'event' => 'payment.completed',
            'version' => 'v1',
            'envelope' => '{}',
            'status' => WebhookDeliveryStatus::Pending,
            'attempts' => 0,
            'occurred_at' => now(),
        ];
    }
}
