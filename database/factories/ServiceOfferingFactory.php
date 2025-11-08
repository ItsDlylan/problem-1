<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ServiceOffering;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceOffering>
 */
final class ServiceOfferingFactory extends Factory
{
    protected $model = ServiceOffering::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'doctor_id' => Doctor::factory(),
            'facility_id' => Facility::factory(),
            'active' => true,
            'default_duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'visibility' => fake()->randomElement(['public', 'private']),
            'meta' => [
                'price' => fake()->randomFloat(2, 50, 500),
            ],
        ];
    }
}

