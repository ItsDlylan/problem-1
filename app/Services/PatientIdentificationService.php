<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Str;

final readonly class PatientIdentificationService
{
    /**
     * Normalize phone number for lookup (remove formatting, handle country codes).
     */
    public function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters
        $normalized = preg_replace('/\D/', '', $phoneNumber);

        // If it starts with 1 and is 11 digits, remove the leading 1 (US country code)
        if (strlen($normalized) === 11 && str_starts_with($normalized, '1')) {
            $normalized = substr($normalized, 1);
        }

        // If it's 10 digits, assume it's a US number
        if (strlen($normalized) === 10) {
            return $normalized;
        }

        // Return as-is if it doesn't match expected patterns
        return $normalized;
    }

    /**
     * Find patient by phone number.
     */
    public function findByPhoneNumber(string $phoneNumber): ?Patient
    {
        $normalized = $this->normalizePhoneNumber($phoneNumber);

        // Try exact match first
        $patient = Patient::where('phone', $normalized)->first();

        if ($patient) {
            return $patient;
        }

        // Try matching normalized versions of stored phone numbers
        // This handles cases where phone numbers are stored with formatting
        $patients = Patient::whereNotNull('phone')->get();

        foreach ($patients as $p) {
            if ($p->phone && $this->normalizePhoneNumber($p->phone) === $normalized) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Verify patient identity using first name, last name, and insurance card number.
     */
    public function verifyPatientIdentity(
        Patient $patient,
        string $firstName,
        string $lastName,
        string $insuranceCardNumber
    ): bool {
        // Normalize names for comparison (case-insensitive, trim whitespace)
        $patientFirstName = strtolower(trim($patient->first_name));
        $patientLastName = strtolower(trim($patient->last_name));
        $providedFirstName = strtolower(trim($firstName));
        $providedLastName = strtolower(trim($lastName));

        // Check name match
        $nameMatches = $patientFirstName === $providedFirstName
            && $patientLastName === $providedLastName;

        if (! $nameMatches) {
            return false;
        }

        // Check insurance card number match (case-insensitive, trim whitespace)
        $patientInsuranceCard = $patient->insurance_card_number
            ? strtolower(trim($patient->insurance_card_number))
            : null;
        $providedInsuranceCard = strtolower(trim($insuranceCardNumber));

        if (! $patientInsuranceCard) {
            // Patient doesn't have insurance card on file, so we can't verify
            return false;
        }

        return $patientInsuranceCard === $providedInsuranceCard;
    }

    /**
     * Create or update patient from call information.
     */
    public function createOrUpdateFromCall(
        string $phoneNumber,
        string $firstName,
        string $lastName,
        string $insuranceCardNumber
    ): Patient {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // Try to find existing patient by phone number
        $patient = $this->findByPhoneNumber($phoneNumber);

        if ($patient) {
            // Update existing patient with provided information
            $patient->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $normalizedPhone,
                'insurance_card_number' => $insuranceCardNumber,
            ]);

            return $patient->fresh();
        }

        // Create new patient
        // Generate a temporary email if not provided (required field)
        $tempEmail = 'temp_'.Str::random(10).'@voice_call.local';

        return Patient::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $tempEmail,
            'phone' => $normalizedPhone,
            'insurance_card_number' => $insuranceCardNumber,
            'password' => bcrypt(Str::random(32)), // Random password, patient can reset later
        ]);
    }
}

