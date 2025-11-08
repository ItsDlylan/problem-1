<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
final class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'), // Default password for testing: 'password'
            'phone' => fake()->phoneNumber(),
            'dob' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'default_insurance_plan_id' => InsurancePlan::factory(),
            'preferred_language' => fake()->randomElement(['en', 'es', 'fr']),
            'meta' => [
                'emergency_contact' => [
                    'name' => fake()->name(),
                    'phone' => fake()->phoneNumber(),
                ],
            ],
        ];
    }
}

