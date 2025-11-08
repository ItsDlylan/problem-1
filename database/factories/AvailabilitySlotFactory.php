<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AvailabilitySlot;
use App\Models\Facility;
use App\Models\Doctor;
use App\Models\ServiceOffering;
use App\Models\AvailabilityRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilitySlot>
 */
final class AvailabilitySlotFactory extends Factory
{
    protected $model = AvailabilitySlot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+1 month');
        $endAt = (clone $startAt)->modify('+30 minutes');

        return [
            'facility_id' => Facility::factory(),
            'doctor_id' => Doctor::factory(),
            'service_offering_id' => ServiceOffering::factory(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => fake()->randomElement(['open', 'reserved', 'booked', 'cancelled']),
            'capacity' => 1,
            'reserved_until' => fake()->boolean(30) ? fake()->dateTimeBetween('now', $startAt) : null,
            'created_from_rule_id' => AvailabilityRule::factory(),
        ];
    }
}

