<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Facility;

use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for facility availability slots API endpoints.
 * Handles fetching availability slots for the authenticated facility.
 */
final readonly class AvailabilitySlotController
{
    /**
     * Get availability slots for the authenticated facility.
     * Supports filtering by date range and doctor.
     *
     * Query parameters:
     * - start_date: Start date for filtering (Y-m-d format)
     * - end_date: End date for filtering (Y-m-d format)
     * - doctor_id: Optional doctor ID to filter by
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get the authenticated facility user
        $facilityUser = Auth::guard('facility')->user();
        
        if (!$facilityUser || !$facilityUser->facility_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Facility user not found.',
            ], 401);
        }

        // Build query for availability slots belonging to this facility
        // Load relationships including patient data for appointments
        $query = AvailabilitySlot::where('facility_id', $facilityUser->facility_id)
            ->with([
                'doctor',
                'serviceOffering',
                'appointments.patient', // Load patient relationship for appointments
            ]);

        // If user is a doctor, automatically filter to show only their own calendar
        // Receptionists and admins can see all doctors' calendars
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            $query->where('doctor_id', $facilityUser->doctor_id);
        } elseif ($request->has('doctor_id') && $request->doctor_id) {
            // For receptionists and admins, allow filtering by doctor if provided
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by date range if provided
        if ($request->has('start_date')) {
            $startDate = $request->start_date;
            $query->whereDate('start_at', '>=', $startDate);
        }

        if ($request->has('end_date')) {
            $endDate = $request->end_date;
            $query->whereDate('start_at', '<=', $endDate);
        }

        // Order by start time
        $query->orderBy('start_at', 'asc');

        // Get paginated results (default 100 per page, can be adjusted)
        $perPage = min((int) $request->get('per_page', 100), 500); // Max 500 per page
        $slots = $query->paginate($perPage);

        // Get IDs of slots that were returned, so we can exclude appointments linked to those slots
        // (to avoid duplicates - those appointments are already included via the slot relationship)
        // Convert items array to collection to use pluck()
        $returnedSlotIds = collect($slots->items())->pluck('id')->toArray();

        // Also fetch appointments that don't have linked slots OR whose linked slots weren't returned
        // These need to be displayed in the calendar even though their slots aren't in the result set
        $appointmentQuery = Appointment::where('facility_id', $facilityUser->facility_id);
        
        // Filter appointments based on whether we have returned slots
        if (!empty($returnedSlotIds)) {
            // If we have returned slots, only get appointments that:
            // 1. Don't have a slot (standalone appointments), OR
            // 2. Have a slot that wasn't in the returned set (slot was filtered out)
            $appointmentQuery->where(function ($query) use ($returnedSlotIds) {
                $query->whereNull('availability_slot_id')
                    ->orWhereNotIn('availability_slot_id', $returnedSlotIds);
            });
        }
        // If no slots were returned, include all appointments (they'll all be virtual slots)
        
        $appointmentQuery->with([
            'doctor',
            'serviceOffering',
            'patient', // Load patient relationship
            'availabilitySlot', // Load the slot relationship to check its date/facility
        ]);

        // Apply same doctor filter as slots
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            $appointmentQuery->where('doctor_id', $facilityUser->doctor_id);
        } elseif ($request->has('doctor_id') && $request->doctor_id) {
            $appointmentQuery->where('doctor_id', $request->doctor_id);
        }

        // Apply date range filter - check BOTH appointment date AND slot date
        // This handles cases where appointment and slot dates differ
        if ($request->has('start_date') || $request->has('end_date')) {
            $appointmentQuery->where(function ($query) use ($request) {
                // Check appointment's own date
                if ($request->has('start_date')) {
                    $query->whereDate('start_at', '>=', $request->start_date);
                }
                if ($request->has('end_date')) {
                    $query->whereDate('start_at', '<=', $request->end_date);
                }
                
                // OR check the linked slot's date (if slot exists and matches date range)
                $query->orWhereHas('availabilitySlot', function ($slotQuery) use ($request) {
                    if ($request->has('start_date')) {
                        $slotQuery->whereDate('start_at', '>=', $request->start_date);
                    }
                    if ($request->has('end_date')) {
                        $slotQuery->whereDate('start_at', '<=', $request->end_date);
                    }
                });
            });
        }

        $standaloneAppointments = $appointmentQuery->orderBy('start_at', 'asc')->get();

        // Transform standalone appointments into slot-like objects for the frontend
        // Create virtual slots from appointments that don't have slots OR whose slots belong to different facilities
        $virtualSlots = $standaloneAppointments->map(function ($appointment) {
            // If appointment has a linked slot, use the slot's date/time instead of appointment's date/time
            // This handles cases where the slot date differs from the appointment date
            // (e.g., slot is Nov 26 but appointment record says Jan 9)
            $startAt = $appointment->start_at;
            $endAt = $appointment->end_at;
            
            if ($appointment->availabilitySlot) {
                // Use slot's date/time for display purposes
                // This ensures the appointment shows up on the correct date in the calendar
                $startAt = $appointment->availabilitySlot->start_at;
                $endAt = $appointment->availabilitySlot->end_at;
                // Use appointment's facility_id (not slot's) since that's what the user belongs to
                // But use the slot's date for filtering/display
            }
            
            // Create a virtual availability slot from the appointment
            // Use negative ID to distinguish from real slots (frontend can handle this)
            $virtualSlot = new AvailabilitySlot();
            $virtualSlot->id = -$appointment->id; // Negative ID to indicate virtual slot
            $virtualSlot->facility_id = $appointment->facility_id; // Use appointment's facility
            $virtualSlot->doctor_id = $appointment->doctor_id;
            $virtualSlot->service_offering_id = $appointment->service_offering_id;
            $virtualSlot->start_at = $startAt; // Use slot's date if available, otherwise appointment's date
            $virtualSlot->end_at = $endAt; // Use slot's date if available, otherwise appointment's date
            // Map appointment status to slot status
            // 'booked' status for appointments that are scheduled/checked_in/in_progress
            // 'cancelled' for cancelled appointments
            // 'booked' for completed appointments (they were booked)
            $slotStatus = match ($appointment->status) {
                'cancelled' => 'cancelled',
                default => 'booked', // scheduled, checked_in, in_progress, completed, no_show all map to 'booked'
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
        // Convert items array to collection, then merge with virtual slots
        $allSlots = collect($slots->items())->merge($virtualSlots)->sortBy('start_at')->values();

        // Calculate total count including virtual slots
        $totalCount = $slots->total() + $virtualSlots->count();

        return response()->json([
            'success' => true,
            'data' => $allSlots->toArray(),
            'meta' => [
                'current_page' => $slots->currentPage(),
                'last_page' => $slots->lastPage(),
                'per_page' => $slots->perPage(),
                'total' => $totalCount,
            ],
        ]);
    }
}

