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
import { DayEventsDialog } from '@/components/Facility/DayEventsDialog';
import { CreateExceptionDialog } from '@/components/Facility/CreateExceptionDialog';
import { EditExceptionDialog } from '@/components/Facility/EditExceptionDialog';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
    getAvailabilitySlots, 
    getDoctors, 
    createAvailabilityException,
    getAvailabilityExceptions,
    updateAvailabilityException,
    deleteAvailabilityException,
} from '@/services/facilityApi';
import type { 
    AvailabilityException,
    AvailabilitySlot, 
    Doctor, 
    CalendarEvent, 
    CreateAvailabilityExceptionParams,
    UpdateAvailabilityExceptionParams,
} from '@/types/facility';
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
    const [exceptions, setExceptions] = useState<AvailabilityException[]>([]);
    const [doctors, setDoctors] = useState<Doctor[]>([]);
    
    // Loading and error states
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    
    // Dialog state for appointment details
    const [selectedEvent, setSelectedEvent] = useState<CalendarEvent | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    // Dialog state for day events (showing all events for a selected day)
    const [selectedDay, setSelectedDay] = useState<Date | null>(null);
    const [isDayEventsDialogOpen, setIsDayEventsDialogOpen] = useState(false);

    // Dialog state for creating availability exception (blocked days)
    const [exceptionStartDate, setExceptionStartDate] = useState<Date | null>(null);
    const [exceptionEndDate, setExceptionEndDate] = useState<Date | null>(null);
    const [isExceptionDialogOpen, setIsExceptionDialogOpen] = useState(false);
    const [isCreatingException, setIsCreatingException] = useState(false);
    
    // Dialog state for editing/deleting availability exception
    const [selectedException, setSelectedException] = useState<AvailabilityException | null>(null);
    const [isEditExceptionDialogOpen, setIsEditExceptionDialogOpen] = useState(false);
    const [isUpdatingException, setIsUpdatingException] = useState(false);
    
    // Refresh trigger to force refetch after creating/updating/deleting exception
    const [refreshTrigger, setRefreshTrigger] = useState(0);

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
    }, [dateRange.start_date, dateRange.end_date, selectedDoctorId, isDoctor, refreshTrigger]);

    // Fetch availability exceptions when date range or doctor filter changes
    useEffect(() => {
        async function fetchExceptions() {
            try {
                const params = {
                    ...dateRange,
                    // Only include doctor_id if it's explicitly set (not null)
                    ...(selectedDoctorId ? { doctor_id: selectedDoctorId } : {}),
                };

                const response = await getAvailabilityExceptions(params);
                
                if (response.success && response.data) {
                    setExceptions(response.data);
                } else {
                    setExceptions([]);
                }
            } catch (err) {
                console.error('Failed to fetch exceptions:', err);
                setExceptions([]);
            }
        }

        fetchExceptions();
    }, [dateRange.start_date, dateRange.end_date, selectedDoctorId, isDoctor, refreshTrigger]);

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

    // Transform slots and exceptions into calendar events for filtering
    // This is similar to what DoctorScheduleCalendar does internally
    const allEvents = useMemo(() => {
        const slotEvents: CalendarEvent[] = slots.map((slot) => {
            const doctorName = slot.doctor?.display_name || `Doctor ${slot.doctor_id}`;
            const serviceName = slot.service_offering
                ? ` - ${slot.service_offering.service_id}`
                : '';
            
            let displayStatus = slot.status;
            const firstAppointment = slot.appointments && slot.appointments.length > 0 ? slot.appointments[0] : null;
            if (firstAppointment) {
                const appointmentStatus = firstAppointment.status;
                if (['no_show', 'completed', 'cancelled', 'checked_in', 'in_progress'].includes(appointmentStatus)) {
                    displayStatus = appointmentStatus;
                }
            }

            let title: string;
            if (isDoctor && displayStatus === 'open') {
                title = displayStatus;
            } else if (isDoctor && firstAppointment && firstAppointment.patient) {
                const patientName = `${firstAppointment.patient.first_name} ${firstAppointment.patient.last_name}`;
                title = `${patientName} (${displayStatus})`;
            } else {
                title = `${doctorName}${serviceName} (${displayStatus})`;
            }

            return {
                id: slot.id,
                title,
                start: new Date(slot.start_at),
                end: new Date(slot.end_at),
                resource: {
                    doctorId: slot.doctor_id,
                    doctorName,
                    slotId: slot.id,
                    status: displayStatus,
                    slotStatus: slot.status,
                    serviceOfferingId: slot.service_offering_id,
                    slot: slot,
                },
            };
        });

        const exceptionEvents: CalendarEvent[] = exceptions.map((exception) => {
            const doctorName = exception.doctor?.display_name || `Doctor ${exception.doctor_id}`;
            const reason = exception.meta?.reason || '';
            const title = reason ? `Blocked: ${reason}` : 'Blocked';

            return {
                id: `exception-${exception.id}`,
                title,
                start: new Date(exception.start_at),
                end: new Date(exception.end_at),
                resource: {
                    doctorId: exception.doctor_id,
                    doctorName,
                    exceptionId: exception.id,
                    status: 'blocked',
                    exception: exception,
                },
            };
        });

        return [...slotEvents, ...exceptionEvents];
    }, [slots, exceptions, isDoctor]);

    // Filter events for the selected day
    const dayEvents = useMemo(() => {
        if (!selectedDay) return [];
        
        const dayStart = startOfDay(selectedDay);
        const dayEnd = endOfDay(selectedDay);
        
        return allEvents.filter((event) => {
            const eventStart = startOfDay(event.start);
            const eventEnd = startOfDay(event.end);
            // Event is on this day if it starts or ends on this day, or spans this day
            return (
                (eventStart >= dayStart && eventStart <= dayEnd) ||
                (eventEnd >= dayStart && eventEnd <= dayEnd) ||
                (eventStart <= dayStart && eventEnd >= dayEnd)
            );
        });
    }, [allEvents, selectedDay]);

    // Handle day click (when user clicks on a day cell)
    const handleDayClick = (date: Date) => {
        setSelectedDay(date);
        setIsDayEventsDialogOpen(true);
    };

    // Handle event selection (when user clicks on a slot or exception)
    const handleSelectEvent = (event: CalendarEvent) => {
        // Check if this is an exception (blocked period)
        // Type guard: check if exceptionId exists in resource
        const resource = event.resource as CalendarEvent['resource'] & { exceptionId?: number; exception?: AvailabilityException };
        if (resource.status === 'blocked' && resource.exceptionId && resource.exception) {
            // Open edit exception dialog
            setSelectedException(resource.exception);
            setIsEditExceptionDialogOpen(true);
        } else {
            // Open appointment details dialog for slots
            setSelectedEvent(event);
            setIsDialogOpen(true);
        }
    };

    // Helper function to check if two date ranges overlap
    const dateRangesOverlap = (start1: Date, end1: Date, start2: Date, end2: Date): boolean => {
        // Two ranges overlap if: start1 <= end2 && start2 <= end1
        return start1 <= end2 && start2 <= end1;
    };

    // Handle slot selection (when doctor drags to select days)
    // Only works for doctors viewing their own calendar
    // Note: This should NOT be triggered by clicking on date cells (those open DayEventsDialog instead)
    const handleSelectSlot = (slotInfo: { start: Date; end: Date }) => {
        if (!isDoctor || !userDoctorId) {
            return; // Only doctors can create exceptions
        }


        // Normalize dates to start of day for comparison
        // Use local date components to avoid timezone shifts
        const selectedStart = new Date(slotInfo.start);
        selectedStart.setHours(0, 0, 0, 0);
        
        const selectedEnd = new Date(slotInfo.end);
        selectedEnd.setHours(0, 0, 0, 0);
        
        // Check if this is a single day selection
        // If start and end are the same date, it's a single day
        const isSingleDay = selectedStart.toDateString() === selectedEnd.toDateString();
        
        if (isSingleDay) {
            // For single day, use the same date for both start and end
            // End will be set to end of day when creating the exception
            selectedEnd.setTime(selectedStart.getTime());
        }

        // Calculate the valid date range for the current view
        // This prevents selecting off-range dates (previous/next month days)
        let validStart: Date;
        let validEnd: Date;
        
        switch (currentView) {
            case 'week':
                validStart = startOfWeek(currentDate, { weekStartsOn: 0 });
                validEnd = endOfWeek(currentDate, { weekStartsOn: 0 });
                break;
            case 'day':
                validStart = startOfDay(currentDate);
                validEnd = endOfDay(currentDate);
                break;
            case 'month':
            default:
                validStart = startOfMonth(currentDate);
                validEnd = endOfMonth(currentDate);
                break;
        }

        // Check if selected dates are within the valid range
        // Allow selection if at least part of the range is within the current view
        const isWithinRange = 
            (selectedStart >= validStart && selectedStart <= validEnd) ||
            (selectedEnd >= validStart && selectedEnd <= validEnd) ||
            (selectedStart <= validStart && selectedEnd >= validEnd);

        if (!isWithinRange) {
            // Don't allow selection of dates outside the current view
            console.log('Selection outside current view range, ignoring');
            return;
        }

        // Clamp selected dates to valid range to prevent off-range selections
        const clampedStart = selectedStart < validStart ? validStart : selectedStart;
        const clampedEnd = selectedEnd > validEnd ? validEnd : selectedEnd;

        // Check if the selected date range overlaps with any existing exceptions
        const overlappingException = exceptions.find((exception) => {
            const exceptionStart = new Date(exception.start_at);
            const exceptionEnd = new Date(exception.end_at);
            
            return dateRangesOverlap(
                clampedStart,
                clampedEnd,
                exceptionStart,
                exceptionEnd
            );
        });

        if (overlappingException) {
            // If there's an overlapping exception, open the edit dialog instead
            setSelectedException(overlappingException);
            setIsEditExceptionDialogOpen(true);
        } else {
            // If no overlap, open the create dialog
            setExceptionStartDate(clampedStart);
            setExceptionEndDate(clampedEnd);
            setIsExceptionDialogOpen(true);
        }
    };

    // Handle creating availability exception
    const handleCreateException = async (params: CreateAvailabilityExceptionParams) => {
        setIsCreatingException(true);
        try {
            const response = await createAvailabilityException(params);
            if (response.success) {
                // Close the dialog and reset state
                setIsExceptionDialogOpen(false);
                setExceptionStartDate(null);
                setExceptionEndDate(null);
                
                // Refresh the calendar to show the new exception
                // Trigger a refetch by incrementing the refresh trigger
                setRefreshTrigger((prev) => prev + 1);
            } else {
                throw new Error(response.message || 'Failed to create exception');
            }
        } catch (err) {
            throw err; // Let the dialog handle the error display
        } finally {
            setIsCreatingException(false);
        }
    };

    // Handle updating availability exception
    const handleUpdateException = async (id: number, params: UpdateAvailabilityExceptionParams) => {
        setIsUpdatingException(true);
        try {
            const response = await updateAvailabilityException(id, params);
            if (response.success) {
                // Close the dialog and reset state
                setIsEditExceptionDialogOpen(false);
                setSelectedException(null);
                
                // Refresh the calendar to show the updated exception
                setRefreshTrigger((prev) => prev + 1);
            } else {
                throw new Error(response.message || 'Failed to update exception');
            }
        } catch (err) {
            throw err; // Let the dialog handle the error display
        } finally {
            setIsUpdatingException(false);
        }
    };

    // Handle deleting availability exception
    const handleDeleteException = async (id: number) => {
        try {
            const response = await deleteAvailabilityException(id);
            if (response.success) {
                // Close the dialog and reset state
                setIsEditExceptionDialogOpen(false);
                setSelectedException(null);
                
                // Refresh the calendar to show the exception is removed
                setRefreshTrigger((prev) => prev + 1);
            } else {
                throw new Error(response.message || 'Failed to delete exception');
            }
        } catch (err) {
            throw err; // Let the dialog handle the error display
        }
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
                                        ? 'View and manage your availability slots for the current month. Drag over days to block availability.'
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
                                    exceptions={exceptions}
                                    currentDate={currentDate}
                                    currentView={currentView}
                                    onNavigate={handleNavigate}
                                    onSelectEvent={handleSelectEvent}
                                    onSelectSlot={handleSelectSlot}
                                    onDayClick={handleDayClick}
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
                                    <div className="flex items-center gap-2">
                                        <div className="h-4 w-4 rounded border bg-gray-600/80" />
                                        <span className="text-foreground">Blocked</span>
                                    </div>
                                </div>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Slots show appointment status when booked. Click on a slot to view details. 
                                    Click on a day to see all appointments for that day.
                                    {isDoctor && ' Click on blocked periods to edit or remove them.'}
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
                isOwnCalendar={isDoctor}
            />

            {/* Day Events Dialog - Shows all events for a selected day */}
            <DayEventsDialog
                events={dayEvents}
                selectedDate={selectedDay}
                open={isDayEventsDialogOpen}
                onOpenChange={setIsDayEventsDialogOpen}
                onEventClick={handleSelectEvent}
                isOwnCalendar={isDoctor}
            />

            {/* Create Exception Dialog - Only shown for doctors */}
            {isDoctor && userDoctorId && (
                <CreateExceptionDialog
                    open={isExceptionDialogOpen}
                    onOpenChange={setIsExceptionDialogOpen}
                    startDate={exceptionStartDate}
                    endDate={exceptionEndDate}
                    doctorId={userDoctorId}
                    onSubmit={handleCreateException}
                    isLoading={isCreatingException}
                />
            )}

            {/* Edit Exception Dialog - Only shown for doctors */}
            {isDoctor && (
                <EditExceptionDialog
                    open={isEditExceptionDialogOpen}
                    onOpenChange={setIsEditExceptionDialogOpen}
                    exception={selectedException}
                    onUpdate={handleUpdateException}
                    onDelete={handleDeleteException}
                    isLoading={isUpdatingException}
                />
            )}
        </AppLayout>
    );
}

