<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AvailabilitySlot;
use App\Models\AvailabilityRule;
use App\Models\Facility;
use App\Models\Doctor;
use App\Models\ServiceOffering;
use Illuminate\Database\Seeder;

/**
 * Seeder for availability_slots table.
 * Creates available time slots based on availability rules.
 * Note: In production, slots would be generated dynamically from rules.
 */
final class AvailabilitySlotSeeder extends Seeder
{
    public function run(): void
    {
        $availabilityRules = AvailabilityRule::where('active', true)->get();

        // Generate slots for the next 30 days based on rules
        // This is a simplified version - in production, you'd generate these dynamically
        foreach ($availabilityRules as $rule) {
            // Create a few sample slots for each rule
            $slotCount = rand(5, 15);

            for ($i = 0; $i < $slotCount; $i++) {
                AvailabilitySlot::factory()->create([
                    'facility_id' => $rule->facility_id,
                    'doctor_id' => $rule->doctor_id,
                    'service_offering_id' => $rule->service_offering_id,
                    'created_from_rule_id' => $rule->id,
                    'status' => fake()->randomElement(['open', 'reserved', 'booked']),
                ]);
            }
        }
    }
}

