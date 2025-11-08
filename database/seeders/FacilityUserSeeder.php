<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Facility;
use App\Models\FacilityUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for facility_users table.
 * Creates facility staff (admins, receptionists, doctors) for testing.
 * 
 * Test Credentials:
 * - Doctor: doctor@facility.com / password
 * - Receptionist: receptionist@facility.com / password
 * - Admin: admin@facility.com / password
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

        $firstFacility = $facilities->first();

        // Create specific test users with known credentials
        $this->createTestUsers($firstFacility);

        // Create additional random users for testing (using factory)
        $this->createRandomUsers($facilities);

        // Create facility users for doctors (link to existing doctors)
        $this->createDoctorUsers($facilities);

        $this->command->info('Created ' . FacilityUser::count() . ' facility users.');
        $this->command->info('');
        $this->command->info('Test Login Credentials:');
        $this->command->info('  Doctor: doctor@facility.com / password');
        $this->command->info('  Receptionist: receptionist@facility.com / password');
        $this->command->info('  Admin: admin@facility.com / password');
    }

    /**
     * Create specific test users with known credentials for easy login.
     */
    private function createTestUsers(Facility $facility): void
    {
        // Create test doctor user
        $doctorUser = FacilityUser::firstOrCreate(
            ['email' => 'doctor@facility.com'],
            [
                'name' => 'Test Doctor',
                'email' => 'doctor@facility.com',
                'password' => Hash::make('password'),
                'facility_id' => $facility->id,
                'role' => 'doctor',
            ]
        );

        // Create test receptionist user
        $receptionistUser = FacilityUser::firstOrCreate(
            ['email' => 'receptionist@facility.com'],
            [
                'name' => 'Test Receptionist',
                'email' => 'receptionist@facility.com',
                'password' => Hash::make('password'),
                'facility_id' => $facility->id,
                'role' => 'receptionist',
            ]
        );

        // Create test admin user
        $adminUser = FacilityUser::firstOrCreate(
            ['email' => 'admin@facility.com'],
            [
                'name' => 'Test Admin',
                'email' => 'admin@facility.com',
                'password' => Hash::make('password'),
                'facility_id' => $facility->id,
                'role' => 'admin',
            ]
        );

        // If doctor user was just created, try to link it to a doctor
        if ($doctorUser->wasRecentlyCreated) {
            $doctor = Doctor::whereNull('facility_user_id')->first();
            if ($doctor) {
                $doctorUser->update(['doctor_id' => $doctor->id]);
                $doctor->update(['facility_user_id' => $doctorUser->id]);
            }
        }
    }

    /**
     * Create additional random users using factory for more test data.
     */
    private function createRandomUsers($facilities): void
    {
        // Create 1-2 additional admins per facility
        foreach ($facilities as $facility) {
            FacilityUser::factory()
                ->count(rand(1, 2))
                ->admin()
                ->create([
                    'facility_id' => $facility->id,
                ]);
        }

        // Create 2-3 additional receptionists per facility
        foreach ($facilities as $facility) {
            FacilityUser::factory()
                ->count(rand(2, 3))
                ->receptionist()
                ->create([
                    'facility_id' => $facility->id,
                ]);
        }
    }

    /**
     * Create facility users for doctors (link to existing doctors).
     */
    private function createDoctorUsers($facilities): void
    {
        $doctors = Doctor::whereNull('facility_user_id')->get();

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
                        'password' => Hash::make('password'), // Default password for all doctors
                        'facility_id' => $firstFacility->id,
                        'doctor_id' => $doctor->id,
                    ]);

                // Link the doctor to this facility user
                $doctor->update([
                    'facility_user_id' => $facilityUser->id,
                ]);
            }
        }
    }
}
