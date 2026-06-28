<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'organizer_id' => Organizer::factory(),
            'category_id' => null,
            'venue_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->optional()->paragraphs(3, true),
            'starts_at' => fake()->optional()->dateTimeBetween('+1 week', '+2 weeks'),
            'ends_at' => null,
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Private,
        ];
    }
}
