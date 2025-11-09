/**
 * TypeScript types for facility-related data structures.
 * Used for facility dashboard, calendar, and availability management.
 */

/**
 * Availability slot represents a specific time slot that can be booked.
 */
export interface AvailabilitySlot {
    id: number;
    facility_id: number;
    doctor_id: number;
    service_offering_id: number | null;
    start_at: string; // ISO datetime string
    end_at: string; // ISO datetime string
    status: 'open' | 'reserved' | 'booked' | 'cancelled';
    capacity: number;
    reserved_until: string | null; // ISO datetime string
    created_from_rule_id: number | null;
    created_at: string;
    updated_at: string;
    // Relationships (loaded via API)
    doctor?: Doctor;
    service_offering?: ServiceOffering;
    appointments?: Appointment[];
}

/**
 * Availability rule defines recurring availability patterns for doctors.
 */
export interface AvailabilityRule {
    id: number;
    doctor_id: number;
    facility_id: number;
    service_offering_id: number | null;
    day_of_week: number; // 0=Sunday, 6=Saturday
    start_time: string; // Time string (HH:mm:ss)
    end_time: string; // Time string (HH:mm:ss)
    slot_duration_minutes: number;
    slot_interval_minutes: number | null;
    active: boolean;
    meta: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    // Relationships (loaded via API)
    doctor?: Doctor;
    service_offering?: ServiceOffering;
}

/**
 * Doctor information for facility dashboard.
 */
export interface Doctor {
    id: number;
    display_name: string;
    first_name: string;
    last_name: string;
    specialty: string | null;
}

/**
 * Service represents a medical service.
 */
export interface Service {
    id: number;
    name: string;
    description: string | null;
    code: string | null;
    code_system: string;
    code_version: string | null;
    default_duration_minutes: number;
    category: string | null;
}

/**
 * Service offering represents a service that a doctor provides at a facility.
 */
export interface ServiceOffering {
    id: number;
    facility_id: number;
    doctor_id: number;
    service_id: number;
    default_duration_minutes: number;
    visibility: string;
    created_at: string;
    updated_at: string;
    service?: Service; // Loaded service relationship
}

/**
 * Patient interface for appointment details (simplified version).
 */
export interface AppointmentPatient {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string | null;
    preferred_language?: string | null;
}

/**
 * Appointment interface (reusing from appointment.ts if available).
 */
export interface Appointment {
    id: number;
    patient_id: number;
    facility_id: number;
    doctor_id: number;
    service_offering_id: number;
    availability_slot_id: number;
    status: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
    // Relationships (loaded via API)
    patient?: AppointmentPatient;
}

/**
 * Calendar event for react-big-calendar.
 * Transformed from AvailabilitySlot for display.
 */
export interface CalendarEvent {
    id: number | string; // Can be slot ID or exception ID string
    title: string;
    start: Date;
    end: Date;
    resource: {
        doctorId: number;
        doctorName: string;
        slotId?: number;
        exceptionId?: number; // ID of the exception if this is a blocked period
        status: AvailabilitySlot['status'] | 'no_show' | 'completed' | 'cancelled' | 'scheduled' | 'checked_in' | 'in_progress' | 'blocked';
        slotStatus?: AvailabilitySlot['status']; // Original slot status
        serviceOfferingId?: number | null;
        slot?: AvailabilitySlot; // Full slot data for details view
        exception?: AvailabilityException; // Full exception data for edit view
    };
}

/**
 * Parameters for fetching availability slots.
 */
export interface GetAvailabilitySlotsParams {
    start_date?: string; // YYYY-MM-DD format
    end_date?: string; // YYYY-MM-DD format
    doctor_id?: number;
    per_page?: number;
    page?: number;
}

/**
 * Parameters for fetching availability rules.
 */
export interface GetAvailabilityRulesParams {
    doctor_id?: number;
}

/**
 * Availability exception represents blocked or modified availability periods.
 */
export interface AvailabilityException {
    id: number;
    availability_rule_id: number | null;
    facility_id: number;
    doctor_id: number;
    start_at: string; // ISO datetime string
    end_at: string; // ISO datetime string
    type: 'blocked' | 'override' | 'emergency';
    meta: {
        reason?: string;
    } | null;
    created_at: string;
    updated_at: string;
    // Relationships (loaded via API)
    doctor?: Doctor;
}

/**
 * Parameters for creating an availability exception.
 */
export interface CreateAvailabilityExceptionParams {
    doctor_id: number;
    start_at: string; // ISO datetime string
    end_at: string; // ISO datetime string
    type?: 'blocked' | 'override' | 'emergency';
    reason?: string;
    availability_rule_id?: number | null;
}

/**
 * Parameters for updating an availability exception.
 */
export interface UpdateAvailabilityExceptionParams {
    start_at?: string; // ISO datetime string
    end_at?: string; // ISO datetime string
    type?: 'blocked' | 'override' | 'emergency';
    reason?: string;
}

/**
 * Parameters for fetching availability exceptions.
 */
export interface GetAvailabilityExceptionsParams {
    start_date?: string; // YYYY-MM-DD format
    end_date?: string; // YYYY-MM-DD format
    doctor_id?: number;
}

/**
 * API response wrapper for paginated data.
 */
export interface PaginatedResponse<T> {
    success: boolean;
    data: T[];
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

/**
 * API response wrapper for non-paginated data.
 */
export interface ApiResponse<T> {
    success: boolean;
    data: T;
    message?: string;
}

