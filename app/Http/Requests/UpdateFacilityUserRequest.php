<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FacilityUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for updating FacilityUser profile information.
 * Validates name and email fields, ensuring email uniqueness.
 */
final class UpdateFacilityUserRequest extends FormRequest
{
    /**
     * Get the validation rules for the request.
     * 
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user('facility');
        assert($user instanceof FacilityUser);

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(FacilityUser::class)->ignore($user->id),
            ],
        ];
    }
}

