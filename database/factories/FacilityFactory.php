<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
final class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company() . ' Medical Center';
        $slug = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'meta' => [
                'timezone' => fake()->timezone(),
                'website' => fake()->url(),
            ],
            'locale' => fake()->randomElement(['en', 'es', 'fr']),
        ];
    }
}

