<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ServiceWorkflow;
use App\Models\ServiceOffering;
use App\Models\InsurancePlan;
use Illuminate\Database\Seeder;

/**
 * Seeder for service_workflows table.
 * Creates workflows for service offerings and insurance plans.
 */
final class ServiceWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $serviceOfferings = ServiceOffering::all();
        $insurancePlans = InsurancePlan::all();

        // Create workflows for service offerings
        // Each service offering gets 1-2 workflows (one generic, possibly one insurance-specific)
        foreach ($serviceOfferings as $offering) {
            // Create a generic workflow (no insurance plan)
            ServiceWorkflow::factory()->create([
                'service_offering_id' => $offering->id,
                'insurance_plan_id' => null,
            ]);

            // Sometimes create an insurance-specific workflow
            if (fake()->boolean(50)) {
                ServiceWorkflow::factory()->create([
                    'service_offering_id' => $offering->id,
                    'insurance_plan_id' => $insurancePlans->random()->id,
                ]);
            }
        }
    }
}

