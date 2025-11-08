<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
final class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => "Dr. {$firstName} {$lastName}",
            'npi' => fake()->numerify('##########'),
            'specialty' => fake()->randomElement([
                'Cardiology',
                'Dermatology',
                'Endocrinology',
                'Gastroenterology',
                'Neurology',
                'Oncology',
                'Orthopedics',
                'Pediatrics',
                'Psychiatry',
                'Radiology',
            ]),
            'profile' => fake()->paragraph(),
            'contact' => [
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
            ],
        ];
    }
}

