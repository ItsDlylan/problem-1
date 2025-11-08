<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ServiceOffering;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\Facility;
use Illuminate\Database\Seeder;

/**
 * Seeder for service_offerings table.
 * Creates service offerings linking services, doctors, and facilities.
 */
final class ServiceOfferingSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::all();
        $doctors = Doctor::all();
        $facilities = Facility::all();

        // Create service offerings for various combinations
        // Each doctor offers 2-4 services at each facility they're associated with
        // Track created offerings to ensure uniqueness within this seeding run
        foreach ($doctors as $doctor) {
            $doctorFacilities = $doctor->facilities;

            foreach ($doctorFacilities as $facility) {
                // Get services that this doctor doesn't already offer at this facility
                // This prevents duplicates even within the same seeding run
                $existingServiceIds = ServiceOffering::where('doctor_id', $doctor->id)
                    ->where('facility_id', $facility->id)
                    ->pluck('service_id')
                    ->toArray();

                $availableServices = $services->reject(function ($service) use ($existingServiceIds) {
                    return in_array($service->id, $existingServiceIds, true);
                });

                // Skip if no available services
                if ($availableServices->isEmpty()) {
                    continue;
                }

                // Select random services from available ones
                $serviceCount = rand(2, min(4, $availableServices->count()));
                $selectedServices = $availableServices->random($serviceCount);

                foreach ($selectedServices as $service) {
                    // Use firstOrCreate to prevent duplicate service offerings
                    // This ensures each doctor has unique service offerings per facility
                    // The unique constraint is on service_id, doctor_id, and facility_id
                    ServiceOffering::firstOrCreate(
                        [
                            'service_id' => $service->id,
                            'doctor_id' => $doctor->id,
                            'facility_id' => $facility->id,
                        ],
                        [
                            'active' => true,
                            'default_duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
                            'visibility' => fake()->randomElement(['public', 'private']),
                            'meta' => [
                                'price' => fake()->randomFloat(2, 50, 500),
                            ],
                        ]
                    );
                }
            }
        }
    }
}

