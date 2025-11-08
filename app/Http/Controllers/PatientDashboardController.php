<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for patient dashboard.
 * Handles displaying the patient dashboard with appointments.
 */
final readonly class PatientDashboardController
{
    /**
     * Show the patient dashboard with appointments.
     */
    public function index(Request $request): Response
    {
        // Get the authenticated patient from the patient guard
        $patient = $request->user('patient');
        assert($patient instanceof Patient);

        // Load appointments with relationships to avoid N+1 queries
        $appointments = $patient->appointments()
            ->with(['doctor', 'facility', 'serviceOffering.service'])
            ->orderBy('start_at', 'desc')
            ->get();

        // Transform appointments to match frontend Appointment type
        $transformedAppointments = $appointments->map(function ($appointment) {
            // Map database status to frontend status
            $statusMap = [
                'scheduled' => 'upcoming',
                'checked_in' => 'upcoming',
                'in_progress' => 'upcoming',
                'completed' => 'complete',
                'cancelled' => 'cancelled',
                'no_show' => 'no show',
            ];

            $frontendStatus = $statusMap[$appointment->status] ?? 'upcoming';

            // Get doctor name
            $doctorName = $appointment->doctor->display_name
                ?? trim("{$appointment->doctor->first_name} {$appointment->doctor->last_name}");

            // Get facility name and address
            $facilityName = $appointment->facility->name;
            $facilityLocation = $appointment->facility->address ?? '';

            // Get service code and description
            $serviceCode = $appointment->serviceOffering->service->code ?? '';
            $serviceDescription = $appointment->serviceOffering->service->description
                ?? $appointment->serviceOffering->service->name
                ?? '';

            return [
                'id' => (string) $appointment->id,
                'doctorName' => $doctorName,
                'facilityName' => $facilityName,
                'facilityLocation' => $facilityLocation,
                'datetime' => $appointment->start_at->toIso8601String(),
                'serviceCode' => [
                    'code' => $serviceCode,
                    'description' => $serviceDescription,
                ],
                'waitlist' => 0, // Not in database schema
                'status' => $frontendStatus,
            ];
        })->toArray();

        return Inertia::render('patient-dashboard', [
            'appointments' => $transformedAppointments,
        ]);
    }
}

