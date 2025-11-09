<?php

declare(strict_types=1);

namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Form request for creating availability exceptions.
 * Validates that the doctor belongs to the facility and dates are valid.
 */
final class CreateAvailabilityExceptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Doctors can only create exceptions for themselves.
     * Receptionists and admins can create exceptions for any doctor in their facility.
     */
    public function authorize(): bool
    {
        $facilityUser = Auth::guard('facility')->user();
        
        if (!$facilityUser || !$facilityUser->facility_id) {
            return false;
        }

        // If user is a doctor, they can only create exceptions for themselves
        if ($facilityUser->role === 'doctor' && $facilityUser->doctor_id) {
            return (int) $this->doctor_id === $facilityUser->doctor_id;
        }

        // Receptionists and admins can create exceptions for any doctor in their facility
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $facilityUser = Auth::guard('facility')->user();
        $facilityId = $facilityUser?->facility_id;

        return [
            'doctor_id' => [
                'required',
                'integer',
                'exists:doctors,id',
                // Ensure doctor belongs to the facility
                Rule::exists('facility_doctors', 'doctor_id')
                    ->where('facility_id', $facilityId)
                    ->where('active', true),
            ],
            'start_at' => [
                'required',
                'date',
                'before_or_equal:end_at',
            ],
            'end_at' => [
                'required',
                'date',
                'after_or_equal:start_at',
            ],
            'type' => [
                'nullable',
                'string',
                Rule::in(['blocked', 'override', 'emergency']),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500', // Reason can be up to 500 characters
            ],
            'availability_rule_id' => [
                'nullable',
                'integer',
                'exists:availability_rules,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Doctor ID is required.',
            'doctor_id.exists' => 'The selected doctor does not exist or does not belong to this facility.',
            'start_at.required' => 'Start date is required.',
            'start_at.date' => 'Start date must be a valid date.',
            'start_at.before_or_equal' => 'Start date must be before or equal to end date.',
            'end_at.required' => 'End date is required.',
            'end_at.date' => 'End date must be a valid date.',
            'end_at.after_or_equal' => 'End date must be after or equal to start date.',
            'type.in' => 'Type must be one of: blocked, override, or emergency.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}

