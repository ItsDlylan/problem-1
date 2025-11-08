<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppointmentStep;
use App\Models\Appointment;
use App\Models\WorkflowStep;
use App\Models\StepTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppointmentStep>
 */
final class AppointmentStepFactory extends Factory
{
    protected $model = AppointmentStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduledStartAt = fake()->dateTimeBetween('now', '+3 months');
        $scheduledEndAt = (clone $scheduledStartAt)->modify('+15 minutes');

        return [
            'appointment_id' => Appointment::factory(),
            'workflow_step_id' => WorkflowStep::factory(),
            'step_template_id' => StepTemplate::factory(),
            'position' => fake()->numberBetween(1, 10),
            'scheduled_start_at' => $scheduledStartAt,
            'scheduled_end_at' => $scheduledEndAt,
            'status' => fake()->randomElement([
                'scheduled',
                'completed',
                'no_show',
                'cancelled',
                'in_progress',
            ]),
            'notes' => fake()->boolean(20) ? fake()->sentence() : null,
            'location' => fake()->randomElement(['Room 101', 'Room 202', 'Lab A', 'Office 1']),
        ];
    }
}

