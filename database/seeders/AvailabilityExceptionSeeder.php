<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\Facility;
use App\Models\Doctor;
use Illuminate\Database\Seeder;

/**
 * Seeder for availability_exceptions table.
 * Creates exceptions to availability rules (blocked times, overrides, etc.).
 */
final class AvailabilityExceptionSeeder extends Seeder
{
    public function run(): void
    {
        $availabilityRules = AvailabilityRule::all();

        // Create a few exceptions (mostly blocked times)
        // Create exceptions for about 20% of rules
        foreach ($availabilityRules->random((int) ($availabilityRules->count() * 0.2)) as $rule) {
            AvailabilityException::factory()->create([
                'availability_rule_id' => $rule->id,
                'facility_id' => $rule->facility_id,
                'doctor_id' => $rule->doctor_id,
                'type' => fake()->randomElement(['blocked', 'override', 'emergency']),
            ]);
        }
    }
}

