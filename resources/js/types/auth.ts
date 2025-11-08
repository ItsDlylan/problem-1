/**
 * Authentication-related TypeScript types for Patient and FacilityUser.
 */

/**
 * Patient model interface for authentication.
 */
export interface Patient {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string | null;
    dob?: string | null;
    preferred_language?: string | null;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
}

/**
 * FacilityUser model interface for authentication.
 * Represents facility staff (doctors, receptionists, admins).
 */
export interface FacilityUser {
    id: number;
    name: string;
    email: string;
    facility_id?: number | null;
    role: 'admin' | 'receptionist' | 'doctor';
    doctor_id?: number | null;
    email_verified_at?: string | null;
    created_at: string;
    updated_at: string;
}

/**
 * Patient login credentials.
 */
export interface PatientLoginCredentials {
    email: string;
    password: string;
    remember?: boolean;
}

/**
 * Patient registration credentials.
 */
export interface PatientRegisterCredentials {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone?: string;
    dob?: string;
}

/**
 * Facility user login credentials.
 */
export interface FacilityLoginCredentials {
    email: string;
    password: string;
    remember?: boolean;
}

