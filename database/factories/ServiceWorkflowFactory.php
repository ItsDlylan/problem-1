<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ServiceWorkflow;
use App\Models\ServiceOffering;
use App\Models\InsurancePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceWorkflow>
 */
final class ServiceWorkflowFactory extends Factory
{
    protected $model = ServiceWorkflow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_offering_id' => ServiceOffering::factory(),
            'insurance_plan_id' => InsurancePlan::factory(),
            'name' => fake()->randomElement([
                'Standard Workflow',
                'Express Workflow',
                'Comprehensive Workflow',
            ]),
            'total_estimated_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'active' => true,
            'meta' => [
                'requires_preparation' => fake()->boolean(30),
            ],
        ];
    }
}

