<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ShowFacilityTwoFactorAuthenticationRequest;
use App\Models\FacilityUser;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

/**
 * Controller for managing FacilityUser two-factor authentication settings.
 * Handles displaying two-factor authentication status and configuration.
 */
final readonly class FacilityTwoFactorAuthenticationController implements HasMiddleware
{
    /**
     * Get the middleware for the controller.
     * Adds password confirmation middleware if two-factor auth is enabled.
     */
    public static function middleware(): array
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [new Middleware('password.confirm:facility', only: ['show'])]
            : [];
    }

    /**
     * Display the facility user two-factor authentication settings page.
     * 
     * Note: This requires FacilityUser to have TwoFactorAuthenticatable trait.
     * If FacilityUser doesn't support 2FA yet, this will need to be updated.
     */
    public function show(ShowFacilityTwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();

        // Get the authenticated facility user from the facility guard
        $user = Auth::guard('facility')->user();
        assert($user instanceof FacilityUser);

        // Check if user has two-factor authentication enabled
        // Note: FacilityUser needs TwoFactorAuthenticatable trait for this to work
        $twoFactorEnabled = method_exists($user, 'hasEnabledTwoFactorAuthentication')
            ? $user->hasEnabledTwoFactorAuthentication()
            : false;

        return Inertia::render('user-two-factor-authentication/show', [
            'twoFactorEnabled' => $twoFactorEnabled,
        ]);
    }
}

