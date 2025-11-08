<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AvailabilityRule;
use App\Models\Doctor;
use App\Models\Facility;
use App\Models\ServiceOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityRule>
 */
final class AvailabilityRuleFactory extends Factory
{
    protected $model = AvailabilityRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'facility_id' => Facility::factory(),
            'service_offering_id' => ServiceOffering::factory(),
            'day_of_week' => fake()->numberBetween(0, 6), // 0=Sun, 6=Sat
            'start_time' => fake()->time('08:00', '10:00'),
            'end_time' => fake()->time('16:00', '18:00'),
            'slot_duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'slot_interval_minutes' => fake()->randomElement([15, 30]),
            'active' => true,
            'meta' => [
                'notes' => fake()->sentence(),
            ],
        ];
    }
}

