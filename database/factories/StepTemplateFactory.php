<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StepTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StepTemplate>
 */
final class StepTemplateFactory extends Factory
{
    protected $model = StepTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Check-in',
                'Vital Signs',
                'Nurse Assessment',
                'Doctor Consultation',
                'Lab Collection',
                'Imaging',
                'Check-out',
                'Payment Processing',
            ]),
            'description' => fake()->sentence(),
            'default_duration_minutes' => fake()->randomElement([5, 10, 15, 20, 30]),
            'requires_location' => fake()->boolean(70),
            'meta' => [
                'requires_staff' => fake()->boolean(80),
            ],
        ];
    }
}

