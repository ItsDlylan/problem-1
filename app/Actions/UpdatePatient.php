<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Patient;

/**
 * Action to update patient profile information.
 * Similar to UpdateUser but works with Patient model.
 */
final readonly class UpdatePatient
{
    /**
     * Update patient profile with provided attributes.
     * If email changes, reset email verification.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Patient $patient, array $attributes): void
    {
        $email = $attributes['email'] ?? null;

        $patient->update([
            ...$attributes,
            ...$patient->email === $email ? [] : ['email_verified_at' => null],
        ]);
    }
}

