<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\Doctor;
use App\Models\ServiceOffering;
use App\Models\AvailabilitySlot;
use App\Models\ServiceWorkflow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
final class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+3 months');
        $endAt = (clone $startAt)->modify('+30 minutes');

        return [
            'patient_id' => Patient::factory(),
            'facility_id' => Facility::factory(),
            'doctor_id' => Doctor::factory(),
            'service_offering_id' => ServiceOffering::factory(),
            'availability_slot_id' => AvailabilitySlot::factory(),
            'service_workflow_id' => ServiceWorkflow::factory(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => fake()->randomElement([
                'scheduled',
                'checked_in',
                'in_progress',
                'completed',
                'no_show',
                'cancelled',
            ]),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'language' => fake()->randomElement(['en', 'es', 'fr']),
            'locale' => fake()->randomElement(['en_US', 'es_US', 'fr_CA']),
            'updated_by' => User::factory(),
        ];
    }
}

