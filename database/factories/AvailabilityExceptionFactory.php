<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\Facility;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityException>
 */
final class AvailabilityExceptionFactory extends Factory
{
    protected $model = AvailabilityException::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+1 month');
        // Ensure end_at is always after start_at by using a clone and modifying it
        $endAt = (clone $startAt)->modify('+' . rand(1, 24) . ' hours');

        return [
            'availability_rule_id' => AvailabilityRule::factory(),
            'facility_id' => Facility::factory(),
            'doctor_id' => Doctor::factory(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'type' => fake()->randomElement(['blocked', 'override', 'emergency']),
            'meta' => [
                'reason' => fake()->sentence(),
            ],
        ];
    }
}

