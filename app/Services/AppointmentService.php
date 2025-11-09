<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Models\Patient;
use App\Models\ServiceOffering;
use App\Models\ServiceWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class AppointmentService
{
    /**
     * Create an appointment from chat-extracted details.
     *
     * @param  array{serviceOfferingId: int, datetime: string}  $details
     * @return Appointment
     */
    public function createFromChat(array $details, Patient $patient): Appointment
    {
        $serviceOfferingId = $details['serviceOfferingId'];
        // Parse datetime and ensure it's in America/Chicago timezone
        $datetime = Carbon::parse($details['datetime'], 'America/Chicago')->setTimezone('America/Chicago');

        // Validate service offering exists and is active
        $serviceOffering = ServiceOffering::with(['service', 'doctor', 'facility'])
            ->where('id', $serviceOfferingId)
            ->where('active', true)
            ->firstOrFail();

        // Get or create availability slot
        $availabilitySlot = $this->findOrCreateAvailabilitySlot(
            $serviceOffering,
            $datetime
        );

        // Get active workflow for this service offering
        $workflow = ServiceWorkflow::where('service_offering_id', $serviceOffering->id)
            ->where('active', true)
            ->firstOrFail();

        // Calculate end time (use service offering duration or workflow total time)
        $durationMinutes = $serviceOffering->default_duration_minutes
            ?? $workflow->total_estimated_minutes
            ?? $serviceOffering->service->default_duration_minutes
            ?? 30;

        $endAt = (clone $datetime)->addMinutes($durationMinutes);

        // Create appointment
        return DB::transaction(function () use (
            $patient,
            $serviceOffering,
            $availabilitySlot,
            $workflow,
            $datetime,
            $endAt
        ) {
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'facility_id' => $serviceOffering->facility_id,
                'doctor_id' => $serviceOffering->doctor_id,
                'service_offering_id' => $serviceOffering->id,
                'availability_slot_id' => $availabilitySlot?->id,
                'service_workflow_id' => $workflow->id,
                'start_at' => $datetime,
                'end_at' => $endAt,
                'status' => 'scheduled',
            ]);

            // Mark slot as booked if it exists
            if ($availabilitySlot) {
                $availabilitySlot->update(['status' => 'booked']);
            }

            return $appointment->load(['serviceOffering.service', 'doctor', 'facility']);
        });
    }

    /**
     * Find or create an availability slot for the given service offering and datetime.
     */
    private function findOrCreateAvailabilitySlot(
        ServiceOffering $serviceOffering,
        Carbon $datetime
    ): ?AvailabilitySlot {
        // Try to find an existing open slot
        $slot = AvailabilitySlot::where('service_offering_id', $serviceOffering->id)
            ->where('status', 'open')
            ->where('start_at', '<=', $datetime)
            ->where('end_at', '>=', $datetime)
            ->first();

        if ($slot) {
            return $slot;
        }

        // If no slot exists, create one (for basic implementation)
        // In a production system, you'd want to check availability rules first
        $durationMinutes = $serviceOffering->default_duration_minutes
            ?? $serviceOffering->service->default_duration_minutes
            ?? 30;

        $endAt = (clone $datetime)->addMinutes($durationMinutes);

        return AvailabilitySlot::create([
            'facility_id' => $serviceOffering->facility_id,
            'doctor_id' => $serviceOffering->doctor_id,
            'service_offering_id' => $serviceOffering->id,
            'start_at' => $datetime,
            'end_at' => $endAt,
            'status' => 'open',
            'capacity' => 1,
        ]);
    }
}

