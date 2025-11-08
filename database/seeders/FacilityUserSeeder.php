<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Facility;
use App\Models\FacilityUser;
use Illuminate\Database\Seeder;

/**
 * Seeder for facility_users table.
 * Creates facility staff (admins, receptionists, doctors) for testing.
 */
final class FacilityUserSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = Facility::all();

        if ($facilities->isEmpty()) {
            $this->command->warn('No facilities found. Please run FacilitySeeder first.');
            return;
        }

        // Create 2-3 admins per facility
        foreach ($facilities as $facility) {
            FacilityUser::factory()
                ->count(rand(2, 3))
                ->admin()
                ->create([
                    'facility_id' => $facility->id,
                ]);
        }

        // Create 3-5 receptionists per facility
        foreach ($facilities as $facility) {
            FacilityUser::factory()
                ->count(rand(3, 5))
                ->receptionist()
                ->create([
                    'facility_id' => $facility->id,
                ]);
        }

        // Create facility users for doctors (link to existing doctors)
        $doctors = Doctor::all();

        foreach ($doctors as $doctor) {
            // Each doctor can belong to 1-3 facilities (via facility_doctors pivot)
            $doctorFacilities = $doctor->facilities()->get();
            
            if ($doctorFacilities->isNotEmpty()) {
                // Create a facility user for this doctor at their first facility
                $firstFacility = $doctorFacilities->first();
                
                // Generate unique email for doctor
                $baseEmail = strtolower($doctor->first_name . '.' . $doctor->last_name . '@facility.com');
                $email = $baseEmail;
                $counter = 1;
                
                // Ensure email is unique
                while (FacilityUser::where('email', $email)->exists()) {
                    $email = strtolower($doctor->first_name . '.' . $doctor->last_name . $counter . '@facility.com');
                    $counter++;
                }
                
                $facilityUser = FacilityUser::factory()
                    ->doctor()
                    ->create([
                        'name' => $doctor->display_name,
                        'email' => $email,
                        'facility_id' => $firstFacility->id,
                        'doctor_id' => $doctor->id,
                    ]);

                // Link the doctor to this facility user
                $doctor->update([
                    'facility_user_id' => $facilityUser->id,
                ]);
            }
        }

        $this->command->info('Created ' . FacilityUser::count() . ' facility users.');
    }
}
