<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\ServiceOffering;
use App\Models\ServiceWorkflow;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder for Doctor 1 demo data.
 * Creates appointments and empty availability slots for the next 2 months.
 * Ensures different services are used for different patients.
 */
final class Doctor1DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Get Doctor 1 - ensure it exists
        $doctor = Doctor::find(1);
        
        if (!$doctor) {
            $message = 'Doctor 1 does not exist. Please run DoctorSeeder first.';
            if ($this->command) {
                $this->command->error($message);
            }
            throw new \RuntimeException($message);
        }

        // Get all active service offerings for Doctor 1
        $serviceOfferings = ServiceOffering::where('doctor_id', $doctor->id)
            ->where('active', true)
            ->get();

        if ($serviceOfferings->isEmpty()) {
            $message = 'No service offerings found for Doctor 1. Please run ServiceOfferingSeeder first.';
            if ($this->command) {
                $this->command->error($message);
            }
            throw new \RuntimeException($message);
        }

        // Get the facility for Doctor 1 (use the first facility from service offerings)
        $facilityId = $serviceOfferings->first()->facility_id;

        // Get all patients (we'll assign different services to different patients)
        $patients = Patient::all();

        if ($patients->isEmpty()) {
            $message = 'No patients found. Please run PatientSeeder first.';
            if ($this->command) {
                $this->command->error($message);
            }
            throw new \RuntimeException($message);
        }

        // Calculate date range for next 2 months
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addMonths(2)->endOfDay();

        // Output info if running from command line, otherwise just proceed silently
        if ($this->command) {
            $this->command->info("Generating demo data for Doctor 1 from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        }

        // Generate appointments for the next 2 months
        // Spread appointments across different days and use different services for different patients
        $appointmentCount = $this->generateAppointments($doctor, $facilityId, $serviceOfferings, $patients, $startDate, $endDate);

        // Generate empty availability slots for the next 2 months
        // Create slots on weekdays (Monday-Friday) during business hours
        $slotCount = $this->generateAvailabilitySlots($doctor, $facilityId, $serviceOfferings, $startDate, $endDate);

        if ($this->command) {
            $this->command->info("Demo data generation completed for Doctor 1.");
            $this->command->info("Created {$appointmentCount} appointments and {$slotCount} empty availability slots.");
        }
    }

    /**
     * Generate appointments for Doctor 1 over the next 2 months.
     * Ensures different services are used for different patients.
     * Returns the count of appointments created.
     */
    private function generateAppointments(
        Doctor $doctor,
        int $facilityId,
        $serviceOfferings,
        $patients,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $appointmentCount = 0;
        $serviceIndex = 0;
        $patientIndex = 0;

        // Generate appointments spread across the 2-month period
        // Create approximately 2-3 appointments per week
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Skip weekends for appointments (or adjust as needed)
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            // Create 0-2 appointments per weekday (randomly)
            $appointmentsToday = rand(0, 2);
            
            for ($i = 0; $i < $appointmentsToday; $i++) {
                // Cycle through service offerings to ensure variety
                $serviceOffering = $serviceOfferings[$serviceIndex % $serviceOfferings->count()];
                $serviceIndex++;

                // Get workflow for this service offering
                $workflow = ServiceWorkflow::where('service_offering_id', $serviceOffering->id)
                    ->where('active', true)
                    ->first();

                if (!$workflow) {
                    continue;
                }

                // Cycle through patients to ensure different patients get different services
                $patient = $patients[$patientIndex % $patients->count()];
                $patientIndex++;

                // Generate appointment time during business hours (9 AM - 5 PM)
                // Try multiple times to find a slot without conflicts
                $attempts = 0;
                $maxAttempts = 10;
                $appointmentCreated = false;

                while ($attempts < $maxAttempts && !$appointmentCreated) {
                    $hour = rand(9, 16); // 9 AM to 4 PM (last appointment starts at 4 PM)
                    $minute = rand(0, 1) * 30; // Either :00 or :30
                    
                    $appointmentStart = $currentDate->copy()->setTime($hour, $minute, 0);
                    
                    // Calculate end time based on service offering duration
                    $duration = $serviceOffering->default_duration_minutes ?? 30;
                    $appointmentEnd = $appointmentStart->copy()->addMinutes($duration);

                    // Skip if appointment would go past end date
                    if ($appointmentEnd->gt($endDate)) {
                        break;
                    }

                    // Check if there's an overlapping appointment for this doctor
                    $hasOverlap = Appointment::where('doctor_id', $doctor->id)
                        ->where('facility_id', $facilityId)
                        ->where(function ($query) use ($appointmentStart, $appointmentEnd) {
                            $query->where(function ($q) use ($appointmentStart, $appointmentEnd) {
                                // Appointment overlaps if it starts before new appointment ends and ends after new appointment starts
                                $q->where('start_at', '<', $appointmentEnd)
                                    ->where('end_at', '>', $appointmentStart);
                            });
                        })
                        ->exists();

                    // If no overlap, create the appointment
                    if (!$hasOverlap) {
                        Appointment::create([
                            'patient_id' => $patient->id,
                            'facility_id' => $facilityId,
                            'doctor_id' => $doctor->id,
                            'service_offering_id' => $serviceOffering->id,
                            'availability_slot_id' => null, // Can be linked later if needed
                            'service_workflow_id' => $workflow->id,
                            'start_at' => $appointmentStart,
                            'end_at' => $appointmentEnd,
                            'status' => fake()->randomElement(['scheduled', 'checked_in', 'completed']),
                            'notes' => fake()->boolean(20) ? fake()->sentence() : null,
                            'language' => fake()->randomElement(['en', 'es', 'fr']),
                            'locale' => fake()->randomElement(['en_US', 'es_US', 'fr_CA']),
                        ]);

                        $appointmentCount++;
                        $appointmentCreated = true;
                    }

                    $attempts++;
                }
            }

            $currentDate->addDay();
        }

        if ($this->command) {
            $this->command->info("Created {$appointmentCount} appointments for Doctor 1.");
        }

        return $appointmentCount;
    }

    /**
     * Generate empty availability slots for Doctor 1 over the next 2 months.
     * Creates slots on weekdays during business hours.
     * Returns the count of slots created.
     */
    private function generateAvailabilitySlots(
        Doctor $doctor,
        int $facilityId,
        $serviceOfferings,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $slotCount = 0;
        $currentDate = $startDate->copy();

        // Generate slots for each weekday
        while ($currentDate->lte($endDate)) {
            // Only create slots on weekdays (Monday-Friday)
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            // Generate slots for each service offering
            foreach ($serviceOfferings as $serviceOffering) {
                // Business hours: 9 AM to 5 PM
                $slotDuration = $serviceOffering->default_duration_minutes ?? 30;
                $slotStartHour = 9;
                $slotEndHour = 17; // 5 PM

                // Create slots every 30 minutes during business hours
                $slotTime = $currentDate->copy()->setTime($slotStartHour, 0, 0);
                $dayEnd = $currentDate->copy()->setTime($slotEndHour, 0, 0);

                while ($slotTime->lt($dayEnd)) {
                    $slotEnd = $slotTime->copy()->addMinutes($slotDuration);

                    // Skip if slot would go past end date
                    if ($slotEnd->gt($endDate)) {
                        break;
                    }

                    // Check if this slot already exists (avoid duplicates)
                    $existingSlot = AvailabilitySlot::where('doctor_id', $doctor->id)
                        ->where('facility_id', $facilityId)
                        ->where('service_offering_id', $serviceOffering->id)
                        ->where('start_at', $slotTime)
                        ->where('end_at', $slotEnd)
                        ->first();

                    if (!$existingSlot) {
                        // Check if there's an overlapping appointment at this time
                        // An appointment overlaps if it starts before the slot ends and ends after the slot starts
                        $hasAppointment = Appointment::where('doctor_id', $doctor->id)
                            ->where('facility_id', $facilityId)
                            ->where(function ($query) use ($slotTime, $slotEnd) {
                                $query->where(function ($q) use ($slotTime, $slotEnd) {
                                    // Appointment starts before slot ends and ends after slot starts
                                    $q->where('start_at', '<', $slotEnd)
                                        ->where('end_at', '>', $slotTime);
                                });
                            })
                            ->exists();

                        // Only create slot if there's no overlapping appointment (keep it empty)
                        if (!$hasAppointment) {
                            AvailabilitySlot::create([
                                'facility_id' => $facilityId,
                                'doctor_id' => $doctor->id,
                                'service_offering_id' => $serviceOffering->id,
                                'start_at' => $slotTime,
                                'end_at' => $slotEnd,
                                'status' => 'open', // Empty slot
                                'capacity' => 1,
                                'reserved_until' => null,
                                'created_from_rule_id' => null,
                            ]);

                            $slotCount++;
                        }
                    }

                    // Move to next slot (every 30 minutes)
                    $slotTime->addMinutes(30);
                }
            }

            $currentDate->addDay();
        }

        if ($this->command) {
            $this->command->info("Created {$slotCount} empty availability slots for Doctor 1.");
        }

        return $slotCount;
    }
}

