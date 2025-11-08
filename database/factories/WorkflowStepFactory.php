<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WorkflowStep;
use App\Models\ServiceWorkflow;
use App\Models\StepTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowStep>
 */
final class WorkflowStepFactory extends Factory
{
    protected $model = WorkflowStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_workflow_id' => ServiceWorkflow::factory(),
            'step_template_id' => StepTemplate::factory(),
            'position' => fake()->numberBetween(1, 10),
            'duration_minutes' => fake()->randomElement([5, 10, 15, 20, 30]),
            'location_type' => fake()->randomElement(['room', 'lab', 'office', 'reception']),
            'requires_preparation' => fake()->boolean(30),
            'can_be_skipped' => fake()->boolean(20),
            'meta' => [
                'notes' => fake()->sentence(),
            ],
        ];
    }
}

