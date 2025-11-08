<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ServiceOffering;
use App\Models\ServiceWorkflow;
use App\Models\AvailabilitySlot;
use Illuminate\Database\Seeder;

/**
 * Seeder for appointments table.
 * Creates sample appointments linking patients, services, and slots.
 */
final class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $serviceOfferings = ServiceOffering::where('active', true)->get();
        $availabilitySlots = AvailabilitySlot::where('status', 'open')->get();

        // Create appointments for some patients
        // Create appointments for about 60% of patients
        foreach ($patients->random((int) ($patients->count() * 0.6)) as $patient) {
            $appointmentCount = rand(1, 3);

            for ($i = 0; $i < $appointmentCount; $i++) {
                $serviceOffering = $serviceOfferings->random();
                $workflow = ServiceWorkflow::where('service_offering_id', $serviceOffering->id)
                    ->where('active', true)
                    ->first();

                if (!$workflow) {
                    continue;
                }

                // Try to find an available slot, or create appointment without slot
                $slot = $availabilitySlots->where('service_offering_id', $serviceOffering->id)
                    ->where('status', 'open')
                    ->first();

                Appointment::factory()->create([
                    'patient_id' => $patient->id,
                    'facility_id' => $serviceOffering->facility_id,
                    'doctor_id' => $serviceOffering->doctor_id,
                    'service_offering_id' => $serviceOffering->id,
                    'availability_slot_id' => $slot?->id,
                    'service_workflow_id' => $workflow->id,
                    'status' => fake()->randomElement(['scheduled', 'checked_in', 'completed']),
                ]);

                // Mark slot as booked if used
                if ($slot) {
                    $slot->update(['status' => 'booked']);
                }
            }
        }
    }
}

