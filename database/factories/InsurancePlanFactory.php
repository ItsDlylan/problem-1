<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InsurancePlan>
 */
final class InsurancePlanFactory extends Factory
{
    protected $model = InsurancePlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $effectiveFrom = fake()->dateTimeBetween('-2 years', 'now');
        $effectiveTo = fake()->dateTimeBetween('now', '+2 years');

        return [
            'insurance_provider_id' => InsuranceProvider::factory(),
            'plan_code' => fake()->bothify('PLAN-###-??'),
            'name' => fake()->randomElement([
                'Basic Plan',
                'Standard Plan',
                'Premium Plan',
                'Gold Plan',
                'Platinum Plan',
            ]),
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'meta' => [
                'deductible' => fake()->randomElement([500, 1000, 2000, 5000]),
                'copay' => fake()->randomElement([20, 30, 50, 100]),
            ],
        ];
    }
}

