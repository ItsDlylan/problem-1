<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AvailabilityRule;
use App\Models\Doctor;
use App\Models\Facility;
use App\Models\ServiceOffering;
use Illuminate\Database\Seeder;

/**
 * Seeder for availability_rules table.
 * Creates availability rules for doctors at facilities.
 */
final class AvailabilityRuleSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::all();

        // Create availability rules for each doctor
        // Each doctor gets 2-4 days per week of availability
        foreach ($doctors as $doctor) {
            $doctorFacilities = $doctor->facilities;

            foreach ($doctorFacilities as $facility) {
                $dayCount = rand(2, 4);
                $allDays = range(0, 6);
                $selectedDayKeys = array_rand($allDays, $dayCount);
                $days = is_array($selectedDayKeys) ? array_map(fn($key) => $allDays[$key], $selectedDayKeys) : [$allDays[$selectedDayKeys]];

                foreach ($days as $day) {
                    $serviceOfferings = ServiceOffering::where('doctor_id', $doctor->id)
                        ->where('facility_id', $facility->id)
                        ->get();

                    AvailabilityRule::factory()->create([
                        'doctor_id' => $doctor->id,
                        'facility_id' => $facility->id,
                        'service_offering_id' => $serviceOfferings->isNotEmpty() ? $serviceOfferings->random()->id : null,
                        'day_of_week' => $day,
                    ]);
                }
            }
        }
    }
}

