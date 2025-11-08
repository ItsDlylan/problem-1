<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\FacilityLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for facility user authentication (login, logout).
 */
final readonly class FacilityAuthController
{
    /**
     * Show the facility login form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/facility/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle facility user login.
     */
    public function store(FacilityLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        // Attempt to authenticate the facility user
        if (Auth::guard('facility')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('facility.dashboard', absolute: false));
        }

        // Authentication failed
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle facility user logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('facility')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
