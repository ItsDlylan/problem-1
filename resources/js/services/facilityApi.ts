/**
 * API service for facility-related endpoints.
 * Handles fetching availability slots, rules, and doctors for the facility dashboard.
 */

import type {
    ApiResponse,
    AvailabilityException,
    AvailabilityRule,
    AvailabilitySlot,
    CreateAvailabilityExceptionParams,
    Doctor,
    GetAvailabilityExceptionsParams,
    GetAvailabilityRulesParams,
    GetAvailabilitySlotsParams,
    PaginatedResponse,
    UpdateAvailabilityExceptionParams,
} from '@/types/facility';

/**
 * Base URL for facility API endpoints.
 * Uses relative URL since we're in the same domain.
 */
const API_BASE = '/api/facility';

/**
 * Helper function to build query string from parameters.
 */
function buildQueryString(params: Record<string, unknown>): string {
    const searchParams = new URLSearchParams();
    
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
            searchParams.append(key, String(value));
        }
    });
    
    const queryString = searchParams.toString();
    return queryString ? `?${queryString}` : '';
}

/**
 * Helper to convert params object to Record for query string building.
 */
function paramsToRecord(params: GetAvailabilitySlotsParams | GetAvailabilityRulesParams): Record<string, unknown> {
    return params as Record<string, unknown>;
}

/**
 * Get CSRF token from meta tag or cookie.
 * Laravel stores CSRF token in meta tag and also in XSRF-TOKEN cookie.
 */
function getCsrfToken(): string | null {
    // Try to get from meta tag first
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        return metaTag.getAttribute('content');
    }
    
    // Fallback to cookie (Laravel stores it as XSRF-TOKEN)
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'XSRF-TOKEN') {
            return decodeURIComponent(value);
        }
    }
    
    return null;
}

/**
 * Helper function to make authenticated API requests.
 * Includes CSRF token and session cookies automatically via browser.
 */
async function fetchApi<T>(
    endpoint: string,
    options: RequestInit = {},
): Promise<T> {
    // Get CSRF token
    const csrfToken = getCsrfToken();
    
    // Build headers with CSRF token for non-GET requests
    const headers: HeadersInit = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...options.headers,
    };
    
    // Add CSRF token for POST, PUT, PATCH, DELETE requests
    if (csrfToken && options.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method.toUpperCase())) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }
    
    const response = await fetch(`${API_BASE}${endpoint}`, {
        ...options,
        headers,
        credentials: 'same-origin', // Include cookies for session auth
    });

    if (!response.ok) {
        // Try to parse error response
        let errorMessage = `API request failed: ${response.status}`;
        try {
            const errorData = await response.json();
            errorMessage = errorData.message || errorData.error || errorMessage;
            
            // Handle authentication errors specifically
            if (response.status === 401 || response.status === 403) {
                errorMessage = 'Unauthenticated. Please log in again.';
            }
        } catch {
            // If JSON parsing fails, use status text
            errorMessage = response.statusText || errorMessage;
        }
        throw new Error(errorMessage);
    }

    return response.json();
}

/**
 * Fetch availability slots for the facility.
 * Supports filtering by date range and doctor.
 *
 * @param params - Query parameters for filtering slots
 * @returns Paginated response with availability slots
 */
export async function getAvailabilitySlots(
    params: GetAvailabilitySlotsParams = {},
): Promise<PaginatedResponse<AvailabilitySlot>> {
    const queryString = buildQueryString(paramsToRecord(params));
    return fetchApi<PaginatedResponse<AvailabilitySlot>>(
        `/availability/slots${queryString}`,
    );
}

/**
 * Fetch availability rules for the facility.
 * Only returns active rules.
 * Supports filtering by doctor.
 *
 * @param params - Query parameters for filtering rules
 * @returns Response with availability rules array
 */
export async function getAvailabilityRules(
    params: GetAvailabilityRulesParams = {},
): Promise<ApiResponse<AvailabilityRule[]>> {
    const queryString = buildQueryString(paramsToRecord(params));
    return fetchApi<ApiResponse<AvailabilityRule[]>>(
        `/availability/rules${queryString}`,
    );
}

/**
 * Fetch all doctors for the authenticated facility.
 *
 * @returns Response with doctors array
 */
export async function getDoctors(): Promise<ApiResponse<Doctor[]>> {
    return fetchApi<ApiResponse<Doctor[]>>('/doctors');
}

/**
 * Fetch a specific doctor by ID for the authenticated facility.
 *
 * @param id - Doctor ID
 * @returns Response with doctor data
 */
export async function getDoctor(id: number): Promise<ApiResponse<Doctor>> {
    return fetchApi<ApiResponse<Doctor>>(`/doctors/${id}`);
}

/**
 * Fetch availability exceptions for the facility.
 * Supports filtering by date range and doctor.
 *
 * @param params - Query parameters for filtering exceptions
 * @returns Response with availability exceptions array
 */
export async function getAvailabilityExceptions(
    params: GetAvailabilityExceptionsParams = {},
): Promise<ApiResponse<AvailabilityException[]>> {
    const queryString = buildQueryString(params as Record<string, unknown>);
    return fetchApi<ApiResponse<AvailabilityException[]>>(
        `/availability/exceptions${queryString}`,
    );
}

/**
 * Create an availability exception (blocked days) for a doctor.
 * Doctors can only create exceptions for themselves.
 * Receptionists and admins can create exceptions for any doctor in their facility.
 *
 * @param params - Parameters for creating the exception
 * @returns Response with created availability exception
 */
export async function createAvailabilityException(
    params: CreateAvailabilityExceptionParams,
): Promise<ApiResponse<AvailabilityException>> {
    return fetchApi<ApiResponse<AvailabilityException>>(
        '/availability/exceptions',
        {
            method: 'POST',
            body: JSON.stringify(params),
        },
    );
}

/**
 * Update an availability exception.
 * Doctors can only update exceptions for themselves.
 * Receptionists and admins can update exceptions for any doctor in their facility.
 *
 * @param id - Exception ID
 * @param params - Parameters for updating the exception
 * @returns Response with updated availability exception
 */
export async function updateAvailabilityException(
    id: number,
    params: UpdateAvailabilityExceptionParams,
): Promise<ApiResponse<AvailabilityException>> {
    return fetchApi<ApiResponse<AvailabilityException>>(
        `/availability/exceptions/${id}`,
        {
            method: 'PUT',
            body: JSON.stringify(params),
        },
    );
}

/**
 * Delete an availability exception (undo blocking).
 * Doctors can only delete exceptions for themselves.
 * Receptionists and admins can delete exceptions for any doctor in their facility.
 *
 * @param id - Exception ID
 * @returns Response indicating success
 */
export async function deleteAvailabilityException(
    id: number,
): Promise<ApiResponse<void>> {
    return fetchApi<ApiResponse<void>>(
        `/availability/exceptions/${id}`,
        {
            method: 'DELETE',
        },
    );
}

/**
 * Initiate a reminder call to +14153519358.
 * When answered, the system will find the patient by phone number
 * and deliver a reminder about their last created appointment.
 *
 * @returns Response with call status
 */
export async function initiateReminderCall(): Promise<ApiResponse<{
    call_sid: string;
    status: string;
    to: string;
    from: string;
}>> {
    return fetchApi<ApiResponse<{
        call_sid: string;
        status: string;
        to: string;
        from: string;
    }>>(
        '/reminder-call/initiate',
        {
            method: 'POST',
        },
    );
}

