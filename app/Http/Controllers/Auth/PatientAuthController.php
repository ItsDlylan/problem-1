<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\PatientLoginRequest;
use App\Http\Requests\PatientRegisterRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for patient authentication (login, logout, registration).
 */
final readonly class PatientAuthController
{
    /**
     * Show the patient login form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/patient/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle patient login.
     */
    public function store(PatientLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        // Attempt to authenticate the patient
        if (Auth::guard('patient')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('patient.dashboard', absolute: false));
        }

        // Authentication failed
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show the patient registration form.
     */
    public function showRegister(Request $request): Response
    {
        return Inertia::render('auth/patient/register', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle patient registration.
     */
    public function register(PatientRegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Create the patient with hashed password
        $patient = Patient::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'dob' => $validated['dob'] ?? null,
        ]);

        // Auto-login after registration
        Auth::guard('patient')->login($patient);

        $request->session()->regenerate();

        return redirect()->intended(route('patient.dashboard', absolute: false));
    }

    /**
     * Handle patient logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('patient')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
