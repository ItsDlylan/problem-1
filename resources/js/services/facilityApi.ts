/**
 * API service for facility-related endpoints.
 * Handles fetching availability slots, rules, and doctors for the facility dashboard.
 */

import type {
    ApiResponse,
    AvailabilityRule,
    AvailabilitySlot,
    Doctor,
    GetAvailabilityRulesParams,
    GetAvailabilitySlotsParams,
    PaginatedResponse,
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
 * Helper function to make authenticated API requests.
 * Includes CSRF token and session cookies automatically via browser.
 */
async function fetchApi<T>(
    endpoint: string,
    options: RequestInit = {},
): Promise<T> {
    const response = await fetch(`${API_BASE}${endpoint}`, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...options.headers,
        },
        credentials: 'same-origin', // Include cookies for session auth
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({
            message: `HTTP error! status: ${response.status}`,
        }));
        throw new Error(errorData.message || `API request failed: ${response.status}`);
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

