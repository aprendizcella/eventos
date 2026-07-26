<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
final class WebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => Organizer::factory(),
            'endpoint' => 'https://'.fake()->unique()->domainName().':443/webhooks/payment-completed',
            'secret' => bin2hex(random_bytes(32)),
            'enabled' => true,
        ];
    }
}
