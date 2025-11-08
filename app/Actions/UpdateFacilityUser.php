<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityUser;

/**
 * Action to update a FacilityUser's profile information.
 * Handles updating name and email, and resets email verification if email changes.
 */
final readonly class UpdateFacilityUser
{
    /**
     * Update the facility user's profile attributes.
     * 
     * @param  array<string, mixed>  $attributes
     */
    public function handle(FacilityUser $user, array $attributes): void
    {
        $email = $attributes['email'] ?? null;

        // Update user attributes
        // If email changes, reset email verification status
        $user->update([
            ...$attributes,
            ...$user->email === $email ? [] : ['email_verified_at' => null],
        ]);
    }
}

