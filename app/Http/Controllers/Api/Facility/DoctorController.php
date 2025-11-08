<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Facility;

use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for facility doctors API endpoints.
 * Handles fetching doctors for the authenticated facility.
 */
final readonly class DoctorController
{
    /**
     * Get all doctors for the authenticated facility.
     * Returns doctors that are associated with the facility through the facility_doctors pivot table.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Get the authenticated facility user
        $facilityUser = Auth::guard('facility')->user();
        
        if (!$facilityUser || !$facilityUser->facility_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Facility user not found.',
            ], 401);
        }

        // Get doctors that belong to this facility through the pivot table
        // Using the facilities relationship on Doctor model
        $doctors = Doctor::whereHas('facilities', function ($query) use ($facilityUser) {
            $query->where('facilities.id', $facilityUser->facility_id)
                ->where('facility_doctors.active', true);
        })
        ->select('id', 'display_name', 'first_name', 'last_name', 'specialty')
        ->orderBy('display_name', 'asc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $doctors,
        ]);
    }

    /**
     * Get a specific doctor by ID for the authenticated facility.
     * Ensures the doctor belongs to the facility.
     *
     * @param int $id Doctor ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Get the authenticated facility user
        $facilityUser = Auth::guard('facility')->user();
        
        if (!$facilityUser || !$facilityUser->facility_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Facility user not found.',
            ], 401);
        }

        // Get doctor that belongs to this facility
        $doctor = Doctor::whereHas('facilities', function ($query) use ($facilityUser) {
            $query->where('facilities.id', $facilityUser->facility_id);
        })
        ->with(['serviceOfferings', 'availabilityRules'])
        ->find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found or does not belong to this facility.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $doctor,
        ]);
    }
}

