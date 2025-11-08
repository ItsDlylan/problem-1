<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateFacilityUser;
use App\Http\Requests\UpdateFacilityUserRequest;
use App\Models\FacilityUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing FacilityUser profile settings.
 * Handles displaying and updating facility user profile information.
 */
final readonly class FacilityProfileController
{
    /**
     * Display the facility user profile edit page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('user-profile/edit', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the facility user's profile information.
     */
    public function update(
        UpdateFacilityUserRequest $request,
        UpdateFacilityUser $action
    ): RedirectResponse {
        // Get the authenticated facility user from the facility guard
        $user = Auth::guard('facility')->user();
        assert($user instanceof FacilityUser);
        
        $action->handle($user, $request->validated());

        return to_route('facility-profile.edit');
    }
}

