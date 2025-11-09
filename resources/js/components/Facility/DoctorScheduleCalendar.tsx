/**
 * DoctorScheduleCalendar component displays availability slots in a monthly calendar view.
 * Uses react-big-calendar to show individual time slots color-coded by doctor.
 */

import { useMemo } from 'react';
import { Calendar, dateFnsLocalizer, View } from 'react-big-calendar';
import { format, parse, startOfWeek, getDay } from 'date-fns';
import { enUS } from 'date-fns/locale';
import 'react-big-calendar/lib/css/react-big-calendar.css';

import type { AvailabilitySlot, CalendarEvent } from '@/types/facility';

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
        if (slot.appointments && slot.appointments.length > 0) {
            // Use the appointment status if it's more specific (e.g., no_show, completed)
            const appointmentStatus = slot.appointments[0].status;
            // Map appointment statuses to display statuses
            if (['no_show', 'completed', 'cancelled', 'checked_in', 'in_progress'].includes(appointmentStatus)) {
                displayStatus = appointmentStatus;
            }
        }

        // For doctors viewing their own calendar, show just the status for "open" slots
        // For other statuses or when viewing other doctors, show doctor name
        let title: string;
        if (isOwnCalendar && displayStatus === 'open') {
            title = displayStatus;
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
    }

    return {
        style: {
            backgroundColor: color,
            borderColor: color,
            opacity,
            color: '#fff',
            borderRadius: '4px',
            border: '2px solid',
            padding: '4px 6px',
            fontSize: '12px',
            fontWeight,
            minHeight: '20px',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            cursor: 'pointer',
        },
    };
}

interface DoctorScheduleCalendarProps {
    slots: AvailabilitySlot[];
    currentDate: Date;
    currentView: View;
    onNavigate: (date: Date, view: View) => void;
    onSelectEvent?: (event: CalendarEvent) => void;
    isOwnCalendar?: boolean; // If true, doctor is viewing their own calendar
}

/**
 * Calendar component that displays doctor availability slots.
 * Supports month, week, and day views.
 * Events are color-coded by status and show individual time slots.
 */
export function DoctorScheduleCalendar({
    slots,
    currentDate,
    currentView,
    onNavigate,
    onSelectEvent,
    isOwnCalendar = false,
}: DoctorScheduleCalendarProps) {
    // Transform slots into calendar events
    // Pass isOwnCalendar flag to show just "open" for open slots when viewing own calendar
    const events = useMemo(() => transformSlotsToEvents(slots, isOwnCalendar), [slots, isOwnCalendar]);

    // Handle event click
    const handleSelectEvent = (event: CalendarEvent) => {
        if (onSelectEvent) {
            onSelectEvent(event);
        }
    };

    // Custom event component for better visibility in month view
    const EventComponent = ({ event }: { event: CalendarEvent }) => {
        const timeStr = `${format(event.start, 'HH:mm')} - ${format(event.end, 'HH:mm')}`;
        // Use event.title which already has the correct logic for showing "open" vs doctor name
        return (
            <div className="rbc-event-content" title={`${event.title} (${timeStr})`}>
                <div className="rbc-event-label">{timeStr}</div>
                <div className="rbc-event-title">{event.title}</div>
            </div>
        );
    };

    // Custom toolbar component to hide react-big-calendar's built-in toolbar
    // We use our own custom controls instead
    const Toolbar = () => null;

    return (
        <div className="h-[600px] w-full rounded-lg border bg-card p-4">
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
                eventPropGetter={eventStyleGetter}
                components={{
                    event: EventComponent,
                    toolbar: Toolbar, // Hide built-in toolbar
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

