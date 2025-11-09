/**
 * Calendar page for facility dashboard.
 * Displays doctor schedules with availability slots in a monthly calendar view.
 * Supports filtering by doctor and month navigation.
 */

import { useState, useEffect, useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { startOfMonth, endOfMonth, startOfWeek, endOfWeek, startOfDay, endOfDay, format, addWeeks, subWeeks, addDays, subDays } from 'date-fns';
import { View } from 'react-big-calendar';

import AppLayout from '@/layouts/app-layout';
import { DoctorScheduleCalendar } from '@/components/Facility/DoctorScheduleCalendar';
import { CalendarFilters } from '@/components/Facility/CalendarFilters';
import { AppointmentDetailsDialog } from '@/components/Facility/AppointmentDetailsDialog';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { getAvailabilitySlots, getDoctors } from '@/services/facilityApi';
import type { AvailabilitySlot, Doctor, CalendarEvent } from '@/types/facility';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { FacilityUser } from '@/types/auth';

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
 * For doctors, automatically shows only their own calendar.
 * For receptionists and admins, shows all doctors' calendars with filter option.
 */
export default function Calendar() {
    // Get authenticated user from Inertia shared data
    const { auth } = usePage<SharedData>().props;
    const facilityUser = auth.user as FacilityUser | null;
    
    // Determine if user is a doctor (doctors can only see their own calendar)
    const isDoctor = facilityUser?.role === 'doctor';
    const userDoctorId = isDoctor && facilityUser?.doctor_id ? facilityUser.doctor_id : null;
    
    // State for current date being viewed
    const [currentDate, setCurrentDate] = useState<Date>(new Date());
    
    // State for calendar view (month, week, day)
    const [currentView, setCurrentView] = useState<View>('month');
    
    // State for selected doctor filter (null = all doctors)
    // For doctors, this is automatically set to their doctor_id
    const [selectedDoctorId, setSelectedDoctorId] = useState<number | null>(userDoctorId);
    
    // State for data
    const [slots, setSlots] = useState<AvailabilitySlot[]>([]);
    const [doctors, setDoctors] = useState<Doctor[]>([]);
    
    // Loading and error states
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    
    // Dialog state for appointment details
    const [selectedEvent, setSelectedEvent] = useState<CalendarEvent | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    // Calculate date range based on current view
    const dateRange = useMemo(() => {
        let start: Date;
        let end: Date;
        
        switch (currentView) {
            case 'week':
                start = startOfWeek(currentDate, { weekStartsOn: 0 }); // Sunday start
                end = endOfWeek(currentDate, { weekStartsOn: 0 });
                break;
            case 'day':
                start = startOfDay(currentDate);
                end = endOfDay(currentDate);
                break;
            case 'month':
            default:
                start = startOfMonth(currentDate);
                end = endOfMonth(currentDate);
                break;
        }
        
        return {
            start_date: format(start, 'yyyy-MM-dd'),
            end_date: format(end, 'yyyy-MM-dd'),
        };
    }, [currentDate, currentView]);

    // Fetch doctors on mount
    // For doctors, this will return only their own doctor record
    // For receptionists/admins, this returns all doctors
    useEffect(() => {
        async function fetchDoctors() {
            try {
                const response = await getDoctors();
                if (response.success && response.data) {
                    setDoctors(response.data);
                    
                    // If user is a doctor and we got their doctor record, ensure selectedDoctorId is set
                    if (isDoctor && userDoctorId && response.data.length > 0) {
                        const doctorRecord = response.data.find(d => d.id === userDoctorId);
                        if (doctorRecord) {
                            setSelectedDoctorId(userDoctorId);
                        }
                    }
                }
            } catch (err) {
                console.error('Failed to fetch doctors:', err);
                // Don't set error for doctors, just log it
            }
        }

        fetchDoctors();
    }, [isDoctor, userDoctorId]);

    // Fetch availability slots when date range or doctor filter changes
    useEffect(() => {
        async function fetchSlots() {
            setIsLoading(true);
            setError(null);

            try {
                const params = {
                    ...dateRange,
                    // Only include doctor_id if it's explicitly set (not null)
                    // For receptionists/admins, null means "show all doctors"
                    // For doctors, selectedDoctorId is already set to their doctor_id
                    ...(selectedDoctorId ? { doctor_id: selectedDoctorId } : {}),
                    per_page: 1000, // Increase limit for calendar view to show all slots in the month
                };

                console.log('Fetching slots with params:', params);
                const response = await getAvailabilitySlots(params);
                
                console.log('Slots response:', {
                    success: response.success,
                    slotCount: response.data?.length || 0,
                    total: response.meta?.total || 0,
                    selectedDoctorId,
                    isDoctor,
                });
                
                if (response.success && response.data) {
                    // Handle paginated response - get all pages if needed
                    let allSlots = response.data;
                    
                    // If there are more pages, fetch them all
                    if (response.meta && response.meta.last_page > 1) {
                        const pages = [];
                        for (let page = 2; page <= response.meta.last_page; page++) {
                            pages.push(
                                getAvailabilitySlots({ ...params, per_page: 1000, page })
                            );
                        }
                        const additionalPages = await Promise.all(pages);
                        additionalPages.forEach((pageResponse) => {
                            if (pageResponse.success && pageResponse.data) {
                                allSlots = [...allSlots, ...pageResponse.data];
                            }
                        });
                    }
                    
                    console.log('Total slots after pagination:', allSlots.length);
                    setSlots(allSlots);
                } else {
                    setError('Failed to load availability slots');
                    setSlots([]);
                }
            } catch (err) {
                console.error('Failed to fetch slots:', err);
                const errorMessage = err instanceof Error ? err.message : 'Failed to load availability slots';
                setError(errorMessage);
                
                // If it's an authentication error, show a more helpful message
                if (errorMessage.includes('Unauthenticated') || errorMessage.includes('401')) {
                    setError('Authentication failed. Please refresh the page or log in again.');
                }
                
                setSlots([]);
            } finally {
                setIsLoading(false);
            }
        }

        fetchSlots();
    }, [dateRange.start_date, dateRange.end_date, selectedDoctorId, isDoctor]);

    // Handle calendar navigation (date/view changes)
    // This is called by react-big-calendar when user interacts with the calendar
    const handleNavigate = (date: Date, view: View) => {
        // Normalize date to start of day to avoid timezone issues
        const normalizedDate = new Date(date);
        normalizedDate.setHours(0, 0, 0, 0);
        setCurrentDate(normalizedDate);
        setCurrentView(view);
    };
    
    // Handle view change
    const handleViewChange = (view: View) => {
        setCurrentView(view);
        // Keep the current date when switching views - don't auto-navigate to today
    };

    // Handle event selection (when user clicks on a slot)
    const handleSelectEvent = (event: CalendarEvent) => {
        setSelectedEvent(event);
        setIsDialogOpen(true);
    };

    // Handle doctor filter change
    // Doctors cannot change the filter (they can only see their own calendar)
    const handleDoctorChange = (doctorId: number | null) => {
        if (!isDoctor) {
            setSelectedDoctorId(doctorId);
        }
    };

    // Handle date change from filters
    const handleDateChange = (date: Date) => {
        // Create a new Date object to ensure React detects the change
        // Normalize the time to start of day to avoid timezone issues
        const newDate = new Date(date);
        newDate.setHours(0, 0, 0, 0);
        setCurrentDate(newDate);
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
                                    {isDoctor ? 'My Schedule Calendar' : 'Doctor Schedule Calendar'}
                                </h1>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {isDoctor
                                        ? 'View and manage your availability slots for the current month.'
                                        : 'View and manage doctor availability slots for the current month. Slots are color-coded by doctor.'}
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
                                        currentView={currentView}
                                        onDoctorChange={handleDoctorChange}
                                        onDateChange={handleDateChange}
                                        onViewChange={handleViewChange}
                                        isLoading={isLoading}
                                        hideDoctorFilter={isDoctor}
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
                                    currentView={currentView}
                                    onNavigate={handleNavigate}
                                    onSelectEvent={handleSelectEvent}
                                    isOwnCalendar={isDoctor}
                                />
                            )}

                            {/* Legend */}
                            <div className="mt-4 rounded-lg border bg-card p-4">
                                <h3 className="mb-2 text-sm font-semibold text-foreground">Legend</h3>
                                <div className="flex flex-wrap gap-4 text-xs">
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-green-500/90" />
                                        <span className="text-foreground">Booked/Completed</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-yellow-500/70" />
                                        <span className="text-foreground">Reserved</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-blue-500/50" />
                                        <span className="text-foreground">Open/Scheduled</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-red-500/85" />
                                        <span className="text-foreground">No Show</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-gray-500/50" />
                                        <span className="text-foreground">Cancelled</span>
                                    </div>
                                </div>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Slots show appointment status when booked. Click on a slot to view details.
                                </p>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
            
            {/* Appointment Details Dialog */}
            <AppointmentDetailsDialog
                event={selectedEvent}
                open={isDialogOpen}
                onOpenChange={setIsDialogOpen}
            />
        </AppLayout>
    );
}

