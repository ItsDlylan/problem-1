<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdatePatient;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for patient profile management.
 * Handles viewing and updating patient profile information.
 */
final readonly class PatientProfileController
{
    /**
     * Show the patient profile edit page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('patient-profile/edit', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the patient profile.
     * Gets the authenticated patient from the patient guard.
     */
    public function update(
        UpdatePatientRequest $request,
        UpdatePatient $action
    ): RedirectResponse {
        // Get the authenticated patient from the patient guard
        $patient = $request->user('patient');
        assert($patient instanceof Patient);
        
        $action->handle($patient, $request->validated());

        return to_route('patient-profile.edit');
    }
}

