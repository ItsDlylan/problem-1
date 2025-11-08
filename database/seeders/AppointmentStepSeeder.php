<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AppointmentStep;
use App\Models\Appointment;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

/**
 * Seeder for appointment_steps table.
 * Creates steps for each appointment based on the workflow.
 */
final class AppointmentStepSeeder extends Seeder
{
    public function run(): void
    {
        $appointments = Appointment::all();

        // Create steps for each appointment based on its workflow
        foreach ($appointments as $appointment) {
            $workflowSteps = WorkflowStep::where('service_workflow_id', $appointment->service_workflow_id)
                ->orderBy('position')
                ->get();

            $currentTime = $appointment->start_at;

            foreach ($workflowSteps as $workflowStep) {
                $duration = $workflowStep->duration_minutes ?? 15;
                $scheduledEndAt = (clone $currentTime)->modify("+{$duration} minutes");

                AppointmentStep::factory()->create([
                    'appointment_id' => $appointment->id,
                    'workflow_step_id' => $workflowStep->id,
                    'step_template_id' => $workflowStep->step_template_id,
                    'position' => $workflowStep->position,
                    'scheduled_start_at' => $currentTime,
                    'scheduled_end_at' => $scheduledEndAt,
                    'status' => fake()->randomElement(['scheduled', 'completed', 'in_progress']),
                ]);

                $currentTime = $scheduledEndAt;
            }
        }
    }
}

