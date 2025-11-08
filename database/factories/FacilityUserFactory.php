<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Facility;
use App\Models\FacilityUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacilityUser>
 */
final class FacilityUserFactory extends Factory
{
    protected $model = FacilityUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'), // Default password for testing: 'password'
            'facility_id' => Facility::factory(),
            'role' => fake()->randomElement(['admin', 'receptionist', 'doctor']),
        ];
    }

    /**
     * Create a facility user with admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Create a facility user with receptionist role.
     */
    public function receptionist(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'receptionist',
        ]);
    }

    /**
     * Create a facility user with doctor role.
     */
    public function doctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'doctor',
        ]);
    }
}
