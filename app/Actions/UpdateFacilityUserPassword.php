<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityUser;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

/**
 * Action to update a FacilityUser's password.
 * Securely hashes the new password before storing it.
 */
final readonly class UpdateFacilityUserPassword
{
    /**
     * Update the facility user's password.
     * 
     * @param  string  $password  The new password (will be hashed)
     */
    public function handle(FacilityUser $user, #[SensitiveParameter] string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);
    }
}

