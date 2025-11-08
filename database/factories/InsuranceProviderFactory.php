<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InsuranceProvider>
 */
final class InsuranceProviderFactory extends Factory
{
    protected $model = InsuranceProvider::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Insurance',
            'website' => fake()->url(),
            'contact' => [
                'phone' => fake()->phoneNumber(),
                'email' => fake()->safeEmail(),
            ],
            'meta' => [
                'type' => fake()->randomElement(['HMO', 'PPO', 'EPO', 'POS']),
            ],
        ];
    }
}

