<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Facility;

use App\Http\Requests\Facility\CreateAvailabilityExceptionRequest;
use App\Http\Requests\Facility\UpdateAvailabilityExceptionRequest;
use App\Models\AvailabilityException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for facility availability exceptions API endpoints.
 * Handles creating, updating, and deleting availability exceptions (blocked days) for doctors.
 */
final readonly class AvailabilityExceptionController
{
    /**
     * Get availability exceptions for the authenticated facility.
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

        // Build query for availability exceptions belonging to this facility
        $query = AvailabilityException::where('facility_id', $facilityUser->facility_id)
            ->with(['doctor', 'facility']);

        // If user is a doctor, automatically filter to show only their own exceptions
        // Receptionists and admins can see all doctors' exceptions
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            $query->where('doctor_id', $facilityUser->doctor_id);
        } elseif ($request->has('doctor_id') && $request->doctor_id) {
            // For receptionists and admins, allow filtering by doctor if provided
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by date range if provided
        // Check if exceptions overlap with the requested date range
        if ($request->has('start_date')) {
            $startDate = $request->start_date;
            $query->whereDate('end_at', '>=', $startDate);
        }

        if ($request->has('end_date')) {
            $endDate = $request->end_date;
            $query->whereDate('start_at', '<=', $endDate);
        }

        // Order by start time
        $query->orderBy('start_at', 'asc');

        // Get all exceptions (no pagination needed as they're typically few)
        $exceptions = $query->get();

        return response()->json([
            'success' => true,
            'data' => $exceptions,
        ]);
    }
    /**
     * Create a new availability exception.
     * Doctors can only create exceptions for themselves.
     * Receptionists and admins can create exceptions for any doctor in their facility.
     *
     * @return JsonResponse
     */
    public function store(CreateAvailabilityExceptionRequest $request): JsonResponse
    {
        // Get the authenticated facility user
        $facilityUser = Auth::guard('facility')->user();
        
        if (!$facilityUser || !$facilityUser->facility_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Facility user not found.',
            ], 401);
        }

        // Validate that doctor belongs to facility
        // If user is a doctor, ensure they can only create exceptions for themselves
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            // Doctors can only create exceptions for themselves
            if ($request->doctor_id !== $facilityUser->doctor_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Doctors can only create exceptions for themselves.',
                ], 403);
            }
        }

        // Create the availability exception
        // The reason will be stored in the meta field as JSON
        $exception = AvailabilityException::create([
            'availability_rule_id' => $request->availability_rule_id,
            'facility_id' => $facilityUser->facility_id,
            'doctor_id' => $request->doctor_id,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'type' => $request->type ?? 'blocked', // Default to 'blocked' if not specified
            'meta' => $request->reason ? ['reason' => $request->reason] : null,
        ]);

        // Load relationships for response
        $exception->load(['doctor', 'facility']);

        return response()->json([
            'success' => true,
            'data' => $exception,
            'message' => 'Availability exception created successfully.',
        ], 201);
    }

    /**
     * Update an existing availability exception.
     * Doctors can only update exceptions for themselves.
     * Receptionists and admins can update exceptions for any doctor in their facility.
     *
     * @param int $id Exception ID
     * @return JsonResponse
     */
    public function update(int $id, UpdateAvailabilityExceptionRequest $request): JsonResponse
    {
        // Get the authenticated facility user
        $facilityUser = Auth::guard('facility')->user();
        
        if (!$facilityUser || !$facilityUser->facility_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Facility user not found.',
            ], 401);
        }

        // Find the exception
        $exception = AvailabilityException::where('facility_id', $facilityUser->facility_id)
            ->find($id);

        if (!$exception) {
            return response()->json([
                'success' => false,
                'message' => 'Availability exception not found.',
            ], 404);
        }

        // Validate that doctor belongs to facility
        // If user is a doctor, ensure they can only update exceptions for themselves
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            if ($exception->doctor_id !== $facilityUser->doctor_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Doctors can only update their own exceptions.',
                ], 403);
            }
        }

        // Update the exception
        $updateData = [];
        
        if ($request->has('start_at')) {
            $updateData['start_at'] = $request->start_at;
        }
        
        if ($request->has('end_at')) {
            $updateData['end_at'] = $request->end_at;
        }
        
        if ($request->has('type')) {
            $updateData['type'] = $request->type;
        }
        
        if ($request->has('reason')) {
            // Update meta field with reason
            $updateData['meta'] = $request->reason 
                ? ['reason' => $request->reason] 
                : null;
        }

        $exception->update($updateData);

        // Reload relationships
        $exception->load(['doctor', 'facility']);

        return response()->json([
            'success' => true,
            'data' => $exception,
            'message' => 'Availability exception updated successfully.',
        ]);
    }

    /**
     * Delete an availability exception (undo blocking).
     * Doctors can only delete exceptions for themselves.
     * Receptionists and admins can delete exceptions for any doctor in their facility.
     *
     * @param int $id Exception ID
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Get the authenticated facility user
        $facilityUser = Auth::guard('facility')->user();
        
        if (!$facilityUser || !$facilityUser->facility_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Facility user not found.',
            ], 401);
        }

        // Find the exception
        $exception = AvailabilityException::where('facility_id', $facilityUser->facility_id)
            ->find($id);

        if (!$exception) {
            return response()->json([
                'success' => false,
                'message' => 'Availability exception not found.',
            ], 404);
        }

        // Validate that doctor belongs to facility
        // If user is a doctor, ensure they can only delete exceptions for themselves
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            if ($exception->doctor_id !== $facilityUser->doctor_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Doctors can only delete their own exceptions.',
                ], 403);
            }
        }

        // Delete the exception
        $exception->delete();

        return response()->json([
            'success' => true,
            'message' => 'Availability exception deleted successfully.',
        ]);
    }
}

