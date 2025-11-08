<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Facility;

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

        return response()->json([
            'success' => true,
            'data' => $slots->items(),
            'meta' => [
                'current_page' => $slots->currentPage(),
                'last_page' => $slots->lastPage(),
                'per_page' => $slots->perPage(),
                'total' => $slots->total(),
            ],
        ]);
    }
}

