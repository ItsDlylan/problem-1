<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Facility;

use App\Models\AvailabilityRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for facility availability rules API endpoints.
 * Handles fetching availability rules for the authenticated facility.
 */
final readonly class AvailabilityRuleController
{
    /**
     * Get availability rules for the authenticated facility.
     * Only returns active rules.
     * Supports filtering by doctor.
     *
     * Query parameters:
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

        // Build query for active availability rules belonging to this facility
        $query = AvailabilityRule::where('facility_id', $facilityUser->facility_id)
            ->where('active', true)
            ->with(['doctor', 'serviceOffering']);

        // Filter by doctor if provided
        if ($request->has('doctor_id') && $request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Order by day of week and start time
        $query->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc');

        // Get all rules (no pagination needed for rules as they're typically few)
        $rules = $query->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }
}

