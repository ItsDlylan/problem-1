/**
 * DoctorScheduleCalendar component displays availability slots in a monthly calendar view.
 * Uses react-big-calendar to show individual time slots color-coded by doctor.
 */

import { useMemo, useEffect, useRef } from 'react';
import type React from 'react';
import { Calendar, dateFnsLocalizer, View } from 'react-big-calendar';
import { format, parse, startOfWeek, getDay, startOfMonth, endOfMonth, startOfWeek as getStartOfWeek, endOfWeek as getEndOfWeek, startOfDay, endOfDay } from 'date-fns';
import { enUS } from 'date-fns/locale';
import 'react-big-calendar/lib/css/react-big-calendar.css';

import type { AvailabilityException, AvailabilitySlot, CalendarEvent } from '@/types/facility';

// Configure date-fns localizer for react-big-calendar
const locales = {
    'en-US': enUS,
};

const localizer = dateFnsLocalizer({
    format,
    parse,
    startOfWeek,
    getDay,
    locales,
});

/**
 * Transform availability exceptions into calendar events for react-big-calendar
 * Exceptions are shown as blocked periods on the calendar
 * 
 * @param exceptions - Array of availability exceptions to transform
 */
function transformExceptionsToEvents(exceptions: AvailabilityException[]): CalendarEvent[] {
    return exceptions.map((exception) => {
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
                exception: exception, // Include full exception data
            },
        };
    });
}

/**
 * Transform availability slots into calendar events for react-big-calendar
 * If a slot has appointments, use the appointment status (e.g., no_show) instead of slot status
 * 
 * @param slots - Array of availability slots to transform
 * @param isOwnCalendar - If true, doctor is viewing their own calendar (hide name for open slots)
 */
function transformSlotsToEvents(slots: AvailabilitySlot[], isOwnCalendar = false): CalendarEvent[] {
    return slots.map((slot) => {
        const doctorName = slot.doctor?.display_name || `Doctor ${slot.doctor_id}`;
        const serviceName = slot.service_offering
            ? ` - ${slot.service_offering.service_id}` // Adjust based on actual service structure
            : '';

        // If slot has appointments, use the first appointment's status (for display purposes)
        // This allows us to show "no_show" status for appointments
        let displayStatus = slot.status;
        const firstAppointment = slot.appointments && slot.appointments.length > 0 ? slot.appointments[0] : null;
        if (firstAppointment) {
            // Use the appointment status if it's more specific (e.g., no_show, completed)
            const appointmentStatus = firstAppointment.status;
            // Map appointment statuses to display statuses
            if (['no_show', 'completed', 'cancelled', 'checked_in', 'in_progress'].includes(appointmentStatus)) {
                displayStatus = appointmentStatus;
            }
        }

        // Determine the title to display based on context
        // For doctors viewing their own calendar:
        // - Show patient name for booked slots (when appointments exist)
        // - Show just the status for "open" slots
        // For other doctors or when not viewing own calendar, show doctor name
        let title: string;
        if (isOwnCalendar && displayStatus === 'open') {
            // Open slots show just the status
            title = displayStatus;
        } else if (isOwnCalendar && firstAppointment && firstAppointment.patient) {
            // When viewing own calendar and slot has appointments, show patient name
            const patientName = `${firstAppointment.patient.first_name} ${firstAppointment.patient.last_name}`;
            title = `${patientName} (${displayStatus})`;
        } else {
            // Default: show doctor name (for other doctors or when not viewing own calendar)
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
                status: displayStatus, // Use display status (appointment status if available)
                slotStatus: slot.status, // Keep original slot status
                serviceOfferingId: slot.service_offering_id,
                slot: slot, // Include full slot data for details view
            },
        };
    });
}

/**
 * Get color based on slot/appointment status (matches legend)
 * - Booked/Completed: Green
 * - Reserved: Yellow
 * - Open: Blue
 * - Cancelled: Gray
 * - No Show: Red/Orange
 * - Blocked: Dark gray/red
 */
function getStatusColor(status: string): string {
    switch (status) {
        case 'booked':
        case 'completed':
        case 'checked_in':
        case 'in_progress':
            return 'rgb(34, 197, 94)'; // green-500
        case 'reserved':
            return 'rgb(234, 179, 8)'; // yellow-500
        case 'open':
        case 'scheduled':
            return 'rgb(59, 130, 246)'; // blue-500
        case 'cancelled':
            return 'rgb(107, 114, 128)'; // gray-500
        case 'no_show':
            return 'rgb(239, 68, 68)'; // red-500
        case 'blocked':
            return 'rgb(75, 85, 99)'; // gray-600 (darker for blocked periods)
        default:
            return 'rgb(107, 114, 128)'; // gray-500 as default
    }
}

/**
 * Custom event style function to color-code events by status
 * Enhanced styling for better visibility in month view
 */
function eventStyleGetter(event: CalendarEvent) {
    // Use status-based color instead of doctor-based color
    const color = getStatusColor(event.resource.status);
    
    // Different opacity and styling based on status
    let opacity = 1;
    let fontWeight = 'normal';
    if (event.resource.status === 'booked' || event.resource.status === 'completed') {
        opacity = 0.9;
        fontWeight = 'bold';
    } else if (event.resource.status === 'reserved') {
        opacity = 0.7;
        fontWeight = '600';
    } else if (event.resource.status === 'open' || event.resource.status === 'scheduled') {
        opacity = 0.5;
    } else if (event.resource.status === 'cancelled') {
        opacity = 0.5;
    } else if (event.resource.status === 'no_show') {
        opacity = 0.85;
        fontWeight = '600';
    } else if (event.resource.status === 'blocked') {
        // Blocked periods should be more visible
        opacity = 0.8;
        fontWeight = '600';
    }

    return {
        style: {
            backgroundColor: color,
            borderColor: color,
            opacity,
            color: '#fff',
            borderRadius: '4px',
            border: '2px solid',
            padding: '6px 8px', // Increased padding for better spacing when multiple events on same day
            fontSize: '12px',
            fontWeight,
            minHeight: '24px', // Increased min-height for better visibility
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            cursor: 'pointer',
            marginBottom: '2px', // Add small margin between stacked events
        },
    };
}

interface DoctorScheduleCalendarProps {
    slots: AvailabilitySlot[];
    exceptions?: AvailabilityException[]; // Availability exceptions (blocked periods)
    currentDate: Date;
    currentView: View;
    onNavigate: (date: Date, view: View) => void;
    onSelectEvent?: (event: CalendarEvent) => void;
    onSelectSlot?: (slotInfo: { start: Date; end: Date }) => void; // Handler for drag selection
    onDayClick?: (date: Date) => void; // Handler for clicking on a day cell
    isOwnCalendar?: boolean; // If true, doctor is viewing their own calendar
}

/**
 * Calendar component that displays doctor availability slots.
 * Supports month, week, and day views.
 * Events are color-coded by status and show individual time slots.
 */
export function DoctorScheduleCalendar({
    slots,
    exceptions = [],
    currentDate,
    currentView,
    onNavigate,
    onSelectEvent,
    onSelectSlot,
    onDayClick,
    isOwnCalendar = false,
}: DoctorScheduleCalendarProps) {
    // Ref to the calendar container to attach event listeners
    const calendarRef = useRef<HTMLDivElement>(null);
    // Flag to track if a date cell was clicked (to prevent onSelectSlot from firing)
    const dateCellClickedRef = useRef(false);

    // Transform slots and exceptions into calendar events
    // Pass isOwnCalendar flag to show just "open" for open slots when viewing own calendar
    const slotEvents = useMemo(() => transformSlotsToEvents(slots, isOwnCalendar), [slots, isOwnCalendar]);
    const exceptionEvents = useMemo(() => transformExceptionsToEvents(exceptions), [exceptions]);
    
    // Combine slot events and exception events
    // Exceptions should appear on top (rendered last) so they're more visible
    const events = useMemo(() => [...slotEvents, ...exceptionEvents], [slotEvents, exceptionEvents]);

    // Attach click handlers to date cells using event delegation
    // This ensures date numbers are clickable
    useEffect(() => {
        if (!onDayClick || !calendarRef.current || currentView !== 'month') return;

        const handleClick = (e: MouseEvent) => {
            const target = e.target as HTMLElement;
            
            // Check if click is on a date cell
            const dateCell = target.closest('.rbc-date-cell');
            if (!dateCell) return;

            // Don't handle clicks on events
            if (target.closest('.rbc-event')) return;

            // Mark that a date cell was clicked to prevent onSelectSlot from firing
            dateCellClickedRef.current = true;
            
            // Stop propagation to prevent other handlers from firing
            e.preventDefault();
            e.stopPropagation();
            
            // Reset the flag after a short delay to allow the day click to process
            setTimeout(() => {
                dateCellClickedRef.current = false;
            }, 100);

            // Prevent default if it's a link
            if (target.tagName === 'A' || target.closest('a')) {
                // Already prevented above
            }

            // Get date number from cell text
            const dateText = (dateCell as HTMLElement).textContent?.trim();
            if (!dateText) return;

            const dayNumber = parseInt(dateText, 10);
            if (isNaN(dayNumber) || dayNumber < 1 || dayNumber > 31) return;

            // Date cells are in header rows above day slots
            // Find the header row and get column index (day of week)
            const headerRow = dateCell.closest('.rbc-row') || dateCell.closest('tr') || dateCell.parentElement;
            if (!headerRow) return;

            const allDateCells = headerRow.querySelectorAll('.rbc-date-cell');
            const columnIndex = Array.from(allDateCells).indexOf(dateCell as Element);
            if (columnIndex < 0) return;

            // Find which week row this header belongs to by finding the corresponding month row
            // Date cells are typically in rows that correspond to week rows
            const monthRows = Array.from(calendarRef.current!.querySelectorAll('.rbc-month-row'));
            
            // Calculate date from grid position
            const monthStart = startOfMonth(currentDate);
            const firstDayOfWeek = getDay(monthStart); // 0 = Sunday
            
            // Find the week row index by checking which month row's date matches
            // We iterate through possible week rows to find the one where this column matches dayNumber
            for (let rowIndex = 0; rowIndex < monthRows.length; rowIndex++) {
                const daysFromStart = (rowIndex * 7) + columnIndex - firstDayOfWeek;
                const testDate = new Date(monthStart);
                testDate.setDate(testDate.getDate() + daysFromStart);
                
                // Check if this date matches the day number we're looking for
                // Also check it's in the current month (or adjacent months shown in calendar)
                if (testDate.getDate() === dayNumber) {
                    onDayClick(testDate);
                    return;
                }
            }
        };

        const calendar = calendarRef.current;
        calendar.addEventListener('click', handleClick, true);

        return () => {
            calendar.removeEventListener('click', handleClick, true);
        };
    }, [onDayClick, currentDate, currentView]);

    // Handle event click
    const handleSelectEvent = (event: CalendarEvent) => {
        if (onSelectEvent) {
            onSelectEvent(event);
        }
    };

    // Handle slot selection (drag to select days)
    // Only enable for doctors viewing their own calendar
    const handleSelectSlot = (slotInfo: { start: Date; end: Date }) => {
        // Don't handle slot selection if a date cell was just clicked
        // Date cell clicks should only open DayEventsDialog, not block availability
        if (dateCellClickedRef.current) {
            return;
        }
        
        if (onSelectSlot && isOwnCalendar) {
            // Normalize dates to start of day for consistency
            const start = new Date(slotInfo.start);
            start.setHours(0, 0, 0, 0);
            
            const end = new Date(slotInfo.end);
            end.setHours(0, 0, 0, 0);
            
            // Check if this is a single day selection
            // react-big-calendar might give us end as start of next day for single day selections
            const startDateStr = start.toDateString();
            const endDateStr = end.toDateString();
            
            // Calculate the difference in days
            const daysDiff = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
            
            // If dates are the same OR end is exactly one day after start (at 00:00:00), it's a single day selection
            if (startDateStr === endDateStr || daysDiff === 1) {
                // Single day selection - use the same date for both
                onSelectSlot({ start, end: new Date(start) });
            } else {
                // Multi-day selection - use as is
                onSelectSlot({ start, end });
            }
        }
    };

    // Custom event component for better visibility in month view
    const EventComponent = ({ event }: { event: CalendarEvent }) => {
        // For blocked exceptions, show full day without time
        // For slots, show time range
        const isException = event.resource.status === 'blocked' && event.resource.exceptionId;
        const timeStr = isException 
            ? 'All Day' 
            : `${format(event.start, 'HH:mm')} - ${format(event.end, 'HH:mm')}`;
        
        // Use event.title which already has the correct logic for showing "open" vs doctor name
        return (
            <div className="rbc-event-content" title={`${event.title} (${timeStr})`}>
                {!isException && <div className="rbc-event-label">{timeStr}</div>}
                <div className="rbc-event-title">{event.title}</div>
            </div>
        );
    };

    // Custom toolbar component to hide react-big-calendar's built-in toolbar
    // We use our own custom controls instead
    const Toolbar = () => null;

    // Custom date cell wrapper to handle day clicks on day slot backgrounds
    // Note: Date cell clicks are handled separately via useEffect event delegation
    const DateCellWrapper = ({ children, value }: { children: React.ReactNode; value: Date }) => {
        const handleClick = (e: React.MouseEvent<HTMLDivElement>) => {
            // Check if the click is on an event - if so, don't handle it
            const target = e.target as HTMLElement;
            const isEvent = target.closest('.rbc-event');
            const isEventContent = target.closest('.rbc-event-content');
            const isDateCell = target.closest('.rbc-date-cell');
            
            // Don't handle clicks on events or date cells (date cells handled separately)
            if (isEvent || isEventContent || isDateCell) {
                return;
            }

            // Handle clicks on day background/empty areas
            e.preventDefault();
            e.stopPropagation();
            if (onDayClick) {
                onDayClick(value);
            }
        };

        return (
            <div 
                onClick={handleClick} 
                className="rbc-day-cell-wrapper" 
                style={{ cursor: 'pointer', height: '100%', width: '100%', position: 'relative' }}
            >
                {children}
            </div>
        );
    };

    // Custom day prop getter to mark off-range days
    // The actual selection prevention is handled in the parent component's handleSelectSlot
    const dayPropGetter = (date: Date) => {
        // Calculate the valid range for the current view
        let validStart: Date;
        let validEnd: Date;
        
        switch (currentView) {
            case 'week':
                validStart = getStartOfWeek(currentDate, { weekStartsOn: 0 });
                validEnd = getEndOfWeek(currentDate, { weekStartsOn: 0 });
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

        // Check if this date is outside the valid range
        const dateOnly = startOfDay(date);
        const isOffRange = dateOnly < startOfDay(validStart) || dateOnly > startOfDay(validEnd);

        return {
            className: isOffRange ? 'rbc-off-range-bg' : '',
            // Add a class to mark off-range days for styling if needed
            // Selection is prevented in handleSelectSlot validation
        };
    };

    return (
        <div ref={calendarRef} className="h-[600px] w-full rounded-lg border bg-card p-4">
            <Calendar
                key={`${currentDate.getTime()}-${currentView}`}
                localizer={localizer}
                events={events}
                startAccessor="start"
                endAccessor="end"
                date={currentDate}
                view={currentView}
                onNavigate={onNavigate}
                onView={onNavigate}
                onSelectEvent={handleSelectEvent}
                onSelectSlot={isOwnCalendar ? handleSelectSlot : undefined} // Only enable drag selection for doctors
                selectable={isOwnCalendar} // Enable selection only for doctors viewing their own calendar
                eventPropGetter={eventStyleGetter}
                dayPropGetter={dayPropGetter} // Disable selection on off-range days
                components={{
                    event: EventComponent,
                    toolbar: Toolbar, // Hide built-in toolbar
                    dateCellWrapper: DateCellWrapper, // Custom day cell wrapper for day clicks
                }}
                popup
                showMultiDayTimes
                step={15} // 15-minute intervals
                timeslots={4} // 4 timeslots per hour (15 min intervals)
                formats={{
                    dayHeaderFormat: (date) => format(date, 'EEE M/d'),
                    dayRangeHeaderFormat: ({ start, end }) =>
                        `${format(start, 'MMM d')} - ${format(end, 'MMM d')}`,
                    timeGutterFormat: (date) => format(date, 'HH:mm'),
                    eventTimeRangeFormat: ({ start, end }) =>
                        `${format(start, 'HH:mm')} - ${format(end, 'HH:mm')}`,
                }}
                className="rbc-calendar"
            />
        </div>
    );
}

