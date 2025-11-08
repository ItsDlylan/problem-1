<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Form request for updating FacilityUser password.
 * Validates current password and new password with confirmation.
 */
final class UpdateFacilityUserPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules for the request.
     * 
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:facility'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}

