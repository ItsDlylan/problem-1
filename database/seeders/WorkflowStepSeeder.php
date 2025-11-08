<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WorkflowStep;
use App\Models\ServiceWorkflow;
use App\Models\StepTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeder for workflow_steps table.
 * Creates steps for each workflow using step templates.
 */
final class WorkflowStepSeeder extends Seeder
{
    public function run(): void
    {
        $serviceWorkflows = ServiceWorkflow::all();
        $stepTemplates = StepTemplate::all();

        // Create 3-6 steps per workflow
        foreach ($serviceWorkflows as $workflow) {
            $stepCount = rand(3, min(6, $stepTemplates->count()));
            $selectedTemplates = $stepTemplates->random($stepCount);

            $position = 1;
            foreach ($selectedTemplates as $template) {
                WorkflowStep::factory()->create([
                    'service_workflow_id' => $workflow->id,
                    'step_template_id' => $template->id,
                    'position' => $position++,
                ]);
            }
        }
    }
}

