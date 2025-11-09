<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Models\FacilityUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for facility dashboard.
 * Handles displaying the facility dashboard with today's calendar summary.
 */
final readonly class FacilityDashboardController
{
    /**
     * Show the facility dashboard with today's calendar summary.
     */
    public function index(Request $request): Response
    {
        // Get the authenticated facility user
        $facilityUser = Auth::guard('facility')->user();
        assert($facilityUser instanceof FacilityUser);

        if (!$facilityUser->facility_id) {
            return Inertia::render('facility-dashboard', [
                'todayStats' => $this->getEmptyStats(),
                'upcomingAppointments' => [],
            ]);
        }

        // Get today's date (uses application timezone from config)
        $today = Carbon::today();
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();

        // Build query for availability slots for today
        // Use whereDate with single date parameter (not range) since whereDate extracts date part
        $slotsQuery = AvailabilitySlot::where('facility_id', $facilityUser->facility_id)
            ->whereDate('start_at', $today)
            ->with([
                'doctor',
                'serviceOffering',
                'appointments.patient', // Load patient relationship for appointments
            ]);

        // If user is a doctor, filter to show only their own calendar
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            $slotsQuery->where('doctor_id', $facilityUser->doctor_id);
        }

        $slots = $slotsQuery->orderBy('start_at', 'asc')->get();

        // Get IDs of slots that were returned
        $returnedSlotIds = $slots->pluck('id')->toArray();

        // Also fetch ALL appointments for today (not just standalone ones)
        // This ensures we capture appointments even if the slot relationship isn't loading properly
        $appointmentQuery = Appointment::where('facility_id', $facilityUser->facility_id);

        // Apply same doctor filter as slots
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            $appointmentQuery->where('doctor_id', $facilityUser->doctor_id);
        }

        // Apply date range filter - check BOTH appointment date AND slot date
        // Note: We check slot date but don't filter by slot facility_id because
        // appointments can have slots from different facilities (data inconsistency)
        $appointmentQuery->where(function ($query) use ($today, $facilityUser) {
            // Check appointment's own date
            $query->whereDate('start_at', $today);

            // OR check the linked slot's date (if slot exists and matches today)
            // Don't filter by slot facility_id - the appointment's facility_id is what matters
            $query->orWhereHas('availabilitySlot', function ($slotQuery) use ($today, $facilityUser) {
                $slotQuery->whereDate('start_at', $today);
                
                // If user is a doctor, filter slot by doctor_id to match appointment's doctor
                if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
                    $slotQuery->where('doctor_id', $facilityUser->doctor_id);
                }
            });
        });

        $allAppointments = $appointmentQuery
            ->with([
                'doctor',
                'serviceOffering',
                'patient',
                'availabilitySlot', // Load the slot relationship to check its date/facility
            ])
            ->orderBy('start_at', 'asc')
            ->get();

        // Ensure appointments are properly attached to their slots
        // Sometimes the relationship doesn't load, so we manually attach them
        $appointmentsBySlotId = $allAppointments->whereNotNull('availability_slot_id')
            ->groupBy('availability_slot_id');

        // Attach appointments to their slots if not already loaded
        foreach ($slots as $slot) {
            if ($appointmentsBySlotId->has($slot->id)) {
                $slotAppointments = $appointmentsBySlotId->get($slot->id);
                // If slot doesn't have appointments loaded, set them
                if (!$slot->appointments || $slot->appointments->isEmpty()) {
                    $slot->setRelation('appointments', $slotAppointments);
                } else {
                    // Merge any missing appointments
                    $existingIds = $slot->appointments->pluck('id')->toArray();
                    $missing = $slotAppointments->filter(function ($apt) use ($existingIds) {
                        return !in_array($apt->id, $existingIds);
                    });
                    if ($missing->isNotEmpty()) {
                        $slot->setRelation('appointments', $slot->appointments->merge($missing));
                    }
                }
            }
        }

        // Filter out appointments that are already included via slots (to avoid duplicates)
        // Get appointment IDs that are already in slots
        $appointmentIdsInSlots = $slots->flatMap(function ($slot) {
            return $slot->appointments ? $slot->appointments->pluck('id') : collect();
        })->unique()->toArray();

        // Only keep appointments that aren't already in slots
        $standaloneAppointments = $allAppointments->filter(function ($appointment) use ($appointmentIdsInSlots) {
            return !in_array($appointment->id, $appointmentIdsInSlots);
        });

        // Transform standalone appointments into slot-like objects
        $virtualSlots = $standaloneAppointments->map(function ($appointment) {
            // If appointment has a linked slot, use the slot's date/time instead of appointment's date/time
            $startAt = $appointment->start_at;
            $endAt = $appointment->end_at;

            if ($appointment->availabilitySlot) {
                // Use slot's date/time for display purposes
                $startAt = $appointment->availabilitySlot->start_at;
                $endAt = $appointment->availabilitySlot->end_at;
            }

            // Create a virtual availability slot from the appointment
            $virtualSlot = new AvailabilitySlot();
            $virtualSlot->id = -$appointment->id; // Negative ID to indicate virtual slot
            $virtualSlot->facility_id = $appointment->facility_id;
            $virtualSlot->doctor_id = $appointment->doctor_id;
            $virtualSlot->service_offering_id = $appointment->service_offering_id;
            $virtualSlot->start_at = $startAt;
            $virtualSlot->end_at = $endAt;

            // Map appointment status to slot status
            $slotStatus = match ($appointment->status) {
                'cancelled' => 'cancelled',
                default => 'booked',
            };
            $virtualSlot->status = $slotStatus;
            $virtualSlot->capacity = 1;
            $virtualSlot->reserved_until = null;
            $virtualSlot->created_from_rule_id = null;
            $virtualSlot->created_at = $appointment->created_at;
            $virtualSlot->updated_at = $appointment->updated_at;

            // Set relationships
            $virtualSlot->setRelation('doctor', $appointment->doctor);
            $virtualSlot->setRelation('serviceOffering', $appointment->serviceOffering);
            // Include the appointment in the appointments relationship
            $virtualSlot->setRelation('appointments', collect([$appointment]));

            return $virtualSlot;
        });

        // Merge real slots with virtual slots
        $allSlots = $slots->merge($virtualSlots)->sortBy('start_at')->values();

        // Calculate statistics
        $stats = $this->calculateStats($allSlots, $today);

        // Get upcoming appointments (next 3)
        $upcomingAppointments = $this->getUpcomingAppointments($allSlots, $today);

        return Inertia::render('facility-dashboard', [
            'todayStats' => $stats,
            'upcomingAppointments' => $upcomingAppointments,
        ]);
    }

    /**
     * Calculate statistics from slots.
     *
     * @param \Illuminate\Support\Collection $slots
     * @param Carbon $today
     * @return array<string, int>
     */
    private function calculateStats($slots, Carbon $today): array
    {
        $booked = 0;
        $open = 0;
        $cancelled = 0;
        $upcoming = 0;

        foreach ($slots as $slot) {
            $slotStart = Carbon::parse($slot->start_at);
            $hasAppointments = $slot->appointments && $slot->appointments->count() > 0;

            // Count booked slots (slots with status 'booked' OR slots that have appointments)
            if ($slot->status === 'booked' || $hasAppointments) {
                $booked++;
                // Count as upcoming if it's today (regardless of time) or in the future
                // This ensures appointments scheduled for today show up even if the time has passed
                if ($slotStart->isSameDay($today) || $slotStart->isFuture()) {
                    $upcoming++;
                }
            } elseif ($slot->status === 'open' && !$hasAppointments) {
                // Only count as open if status is open AND has no appointments
                $open++;
            } elseif ($slot->status === 'cancelled') {
                $cancelled++;
            } elseif ($slot->status === 'reserved') {
                // Reserved slots should also be counted (they're not open)
                // But we don't count them as booked unless they have appointments
                if ($hasAppointments) {
                    $booked++;
                    if ($slotStart->isSameDay($today) || $slotStart->isFuture()) {
                        $upcoming++;
                    }
                }
            }
        }

        return [
            'total' => $slots->count(),
            'booked' => $booked,
            'open' => $open,
            'cancelled' => $cancelled,
            'upcoming' => $upcoming,
        ];
    }

    /**
     * Get upcoming appointments (next 3).
     * Includes all appointments scheduled for today or in the future.
     *
     * @param \Illuminate\Support\Collection $slots
     * @param Carbon $today
     * @return array<int, array<string, mixed>>
     */
    private function getUpcomingAppointments($slots, Carbon $today): array
    {
        $upcoming = $slots
            ->filter(function ($slot) use ($today) {
                $slotStart = Carbon::parse($slot->start_at);
                $hasAppointments = $slot->appointments && $slot->appointments->count() > 0;
                
                // Include slots that:
                // 1. Are today or in the future (compare dates, not full datetime)
                // 2. Have status 'booked' OR have appointments (including scheduled appointments)
                return ($slotStart->isSameDay($today) || $slotStart->isFuture()) && (
                    $slot->status === 'booked' ||
                    $hasAppointments
                );
            })
            ->sortBy('start_at')
            ->take(3)
            ->map(function ($slot) {
                $slotStart = Carbon::parse($slot->start_at);
                $slotEnd = Carbon::parse($slot->end_at);
                $appointment = $slot->appointments?->first();

                return [
                    'id' => $slot->id,
                    'start_at' => $slotStart->toIso8601String(),
                    'end_at' => $slotEnd->toIso8601String(),
                    'status' => $appointment?->status ?? $slot->status,
                    'patient' => $appointment?->patient ? [
                        'first_name' => $appointment->patient->first_name,
                        'last_name' => $appointment->patient->last_name,
                    ] : null,
                    'doctor' => $slot->doctor ? [
                        'display_name' => $slot->doctor->display_name,
                    ] : null,
                ];
            })
            ->values()
            ->toArray();

        return $upcoming;
    }

    /**
     * Get empty stats array.
     *
     * @return array<string, int>
     */
    private function getEmptyStats(): array
    {
        return [
            'total' => 0,
            'booked' => 0,
            'open' => 0,
            'cancelled' => 0,
            'upcoming' => 0,
        ];
    }
}

