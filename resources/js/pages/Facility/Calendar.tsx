/**
 * Calendar page for facility dashboard.
 * Displays doctor schedules with availability slots in a monthly calendar view.
 * Supports filtering by doctor and month navigation.
 */

import { useState, useEffect, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { startOfMonth, endOfMonth, format } from 'date-fns';
import { View } from 'react-big-calendar';

import AppLayout from '@/layouts/app-layout';
import { DoctorScheduleCalendar } from '@/components/Facility/DoctorScheduleCalendar';
import { CalendarFilters } from '@/components/Facility/CalendarFilters';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { getAvailabilitySlots, getDoctors } from '@/services/facilityApi';
import type { AvailabilitySlot, Doctor, CalendarEvent } from '@/types/facility';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/facility/dashboard',
    },
    {
        title: 'Calendar',
        href: '/facility/calendar',
    },
];

/**
 * Main calendar page component.
 * Fetches availability slots and doctors, handles filtering and date navigation.
 */
export default function Calendar() {
    // State for current month being viewed
    const [currentDate, setCurrentDate] = useState<Date>(new Date());
    
    // State for selected doctor filter (null = all doctors)
    const [selectedDoctorId, setSelectedDoctorId] = useState<number | null>(null);
    
    // State for data
    const [slots, setSlots] = useState<AvailabilitySlot[]>([]);
    const [doctors, setDoctors] = useState<Doctor[]>([]);
    
    // Loading and error states
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    // Calculate date range for current month view
    const dateRange = useMemo(() => {
        const start = startOfMonth(currentDate);
        const end = endOfMonth(currentDate);
        return {
            start_date: format(start, 'yyyy-MM-dd'),
            end_date: format(end, 'yyyy-MM-dd'),
        };
    }, [currentDate]);

    // Fetch doctors on mount
    useEffect(() => {
        async function fetchDoctors() {
            try {
                const response = await getDoctors();
                if (response.success && response.data) {
                    setDoctors(response.data);
                }
            } catch (err) {
                console.error('Failed to fetch doctors:', err);
                // Don't set error for doctors, just log it
            }
        }

        fetchDoctors();
    }, []);

    // Fetch availability slots when date range or doctor filter changes
    useEffect(() => {
        async function fetchSlots() {
            setIsLoading(true);
            setError(null);

            try {
                const params = {
                    ...dateRange,
                    doctor_id: selectedDoctorId || undefined,
                };

                const response = await getAvailabilitySlots(params);
                
                if (response.success && response.data) {
                    setSlots(response.data);
                } else {
                    setError('Failed to load availability slots');
                }
            } catch (err) {
                console.error('Failed to fetch slots:', err);
                setError(err instanceof Error ? err.message : 'Failed to load availability slots');
                setSlots([]);
            } finally {
                setIsLoading(false);
            }
        }

        fetchSlots();
    }, [dateRange.start_date, dateRange.end_date, selectedDoctorId]);

    // Handle calendar navigation (month changes)
    const handleNavigate = (date: Date, view: View) => {
        setCurrentDate(date);
    };

    // Handle event selection (when user clicks on a slot)
    const handleSelectEvent = (event: CalendarEvent) => {
        // TODO: Show slot details in a modal or navigate to detail page
        console.log('Selected event:', event);
        // You can add a modal or navigation here
    };

    // Handle doctor filter change
    const handleDoctorChange = (doctorId: number | null) => {
        setSelectedDoctorId(doctorId);
    };

    // Handle date change from filters
    const handleDateChange = (date: Date) => {
        setCurrentDate(date);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facility Calendar" />
            
            <div className="flex h-screen overflow-hidden bg-gray-50/50 transition-all duration-300 dark:bg-gray-900/50">
                <div className="flex flex-1 flex-col">
                    <main className="flex-grow overflow-y-auto p-4 md:p-6">
                        <div className="mx-auto max-w-7xl">
                            {/* Page Header */}
                            <div className="mb-6">
                                <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    Doctor Schedule Calendar
                                </h1>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    View and manage doctor availability slots for the current month.
                                    Slots are color-coded by doctor.
                                </p>
                            </div>

                            {/* Filters */}
                            <div className="mb-4">
                                {isLoading && doctors.length === 0 ? (
                                    <Skeleton className="h-16 w-full" />
                                ) : (
                                    <CalendarFilters
                                        doctors={doctors}
                                        selectedDoctorId={selectedDoctorId}
                                        currentDate={currentDate}
                                        onDoctorChange={handleDoctorChange}
                                        onDateChange={handleDateChange}
                                        isLoading={isLoading}
                                    />
                                )}
                            </div>

                            {/* Error Message */}
                            {error && (
                                <Alert variant="destructive" className="mb-4">
                                    <AlertDescription>{error}</AlertDescription>
                                </Alert>
                            )}

                            {/* Calendar */}
                            {isLoading ? (
                                <Skeleton className="h-[600px] w-full" />
                            ) : (
                                <DoctorScheduleCalendar
                                    slots={slots}
                                    currentDate={currentDate}
                                    onNavigate={handleNavigate}
                                    onSelectEvent={handleSelectEvent}
                                />
                            )}

                            {/* Legend */}
                            <div className="mt-4 rounded-lg border bg-card p-4">
                                <h3 className="mb-2 text-sm font-semibold">Legend</h3>
                                <div className="flex flex-wrap gap-4 text-xs">
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-green-500/90" />
                                        <span>Booked</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-yellow-500/70" />
                                        <span>Reserved</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-blue-500/50" />
                                        <span>Open</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-gray-500/50" />
                                        <span>Cancelled</span>
                                    </div>
                                </div>
                                <p className="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    Each doctor has a unique color. Click on a slot to view details.
                                </p>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </AppLayout>
    );
}

