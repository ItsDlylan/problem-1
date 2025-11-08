<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
final class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $codeSystems = ['CPT', 'HCPCS', 'ICD', 'Custom'];
        $codeSystem = fake()->randomElement($codeSystems);

        return [
            'name' => fake()->randomElement([
                'General Consultation',
                'Follow-up Visit',
                'Lab Work',
                'X-Ray',
                'MRI Scan',
                'Blood Test',
                'Physical Examination',
                'Vaccination',
            ]),
            'description' => fake()->sentence(),
            'code' => fake()->numerify('#####'),
            'code_system' => $codeSystem,
            'code_version' => fake()->year(),
            'default_duration_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
            'category' => fake()->randomElement([
                'Consultation',
                'Diagnostic',
                'Treatment',
                'Preventive',
            ]),
            'meta' => [
                'requires_referral' => fake()->boolean(20),
            ],
        ];
    }
}

