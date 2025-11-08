<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateFacilityUserPassword;
use App\Http\Requests\UpdateFacilityUserPasswordRequest;
use App\Models\FacilityUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing FacilityUser password settings.
 * Handles displaying and updating facility user password.
 */
final readonly class FacilityPasswordController
{
    /**
     * Display the facility user password edit page.
     */
    public function edit(): Response
    {
        return Inertia::render('user-password/edit');
    }

    /**
     * Update the facility user's password.
     */
    public function update(
        UpdateFacilityUserPasswordRequest $request,
        UpdateFacilityUserPassword $action
    ): RedirectResponse {
        // Get the authenticated facility user from the facility guard
        $user = Auth::guard('facility')->user();
        assert($user instanceof FacilityUser);
        
        $action->handle($user, $request->string('password')->value());

        return back();
    }
}

