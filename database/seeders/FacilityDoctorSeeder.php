<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder for facility_doctors pivot table.
 * Links doctors to facilities with roles.
 */
final class FacilityDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = Facility::all();
        $doctors = Doctor::all();

        // Link each doctor to 1-3 facilities
        foreach ($doctors as $doctor) {
            $facilityCount = rand(1, min(3, $facilities->count()));
            $selectedFacilities = $facilities->random($facilityCount);

            foreach ($selectedFacilities as $facility) {
                DB::table('facility_doctors')->insert([
                    'facility_id' => $facility->id,
                    'doctor_id' => $doctor->id,
                    'role' => fake()->randomElement(['Primary', 'Consultant', 'Specialist', 'Attending']),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

