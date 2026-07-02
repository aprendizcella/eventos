<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActiveCheckIn;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActiveCheckIn>
 */
final class ActiveCheckInFactory extends Factory
{
    protected $model = ActiveCheckIn::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'check_in_list_id' => CheckInList::factory(),
            'attendee_id' => Attendee::factory(),
            'checked_in_at' => now(),
            'checked_in_by_user_id' => User::factory(),
        ];
    }
}
