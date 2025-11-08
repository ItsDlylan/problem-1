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
        foreach ($doctors as $doctor) {
            $doctorFacilities = $doctor->facilities;

            foreach ($doctorFacilities as $facility) {
                $serviceCount = rand(2, min(4, $services->count()));
                $selectedServices = $services->random($serviceCount);

                foreach ($selectedServices as $service) {
                    ServiceOffering::factory()->create([
                        'service_id' => $service->id,
                        'doctor_id' => $doctor->id,
                        'facility_id' => $facility->id,
                    ]);
                }
            }
        }
    }
}

