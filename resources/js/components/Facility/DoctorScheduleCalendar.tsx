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
 * Color palette for doctors (generates consistent colors per doctor ID)
 * Uses HSL color space for better color distribution
 */
function getDoctorColor(doctorId: number): string {
    // Generate a hue based on doctor ID (0-360 degrees)
    const hue = (doctorId * 137.508) % 360; // Golden angle approximation for better distribution
    // Use moderate saturation and lightness for good visibility
    return `hsl(${hue}, 70%, 50%)`;
}

/**
 * Transform availability slots into calendar events for react-big-calendar
 */
function transformSlotsToEvents(slots: AvailabilitySlot[]): CalendarEvent[] {
    return slots.map((slot) => {
        const doctorName = slot.doctor?.display_name || `Doctor ${slot.doctor_id}`;
        const serviceName = slot.service_offering
            ? ` - ${slot.service_offering.service_id}` // Adjust based on actual service structure
            : '';

        return {
            id: slot.id,
            title: `${doctorName}${serviceName} (${slot.status})`,
            start: new Date(slot.start_at),
            end: new Date(slot.end_at),
            resource: {
                doctorId: slot.doctor_id,
                doctorName,
                slotId: slot.id,
                status: slot.status,
                serviceOfferingId: slot.service_offering_id,
            },
        };
    });
}

/**
 * Custom event style function to color-code events by doctor
 */
function eventStyleGetter(event: CalendarEvent) {
    const color = getDoctorColor(event.resource.doctorId);
    
    // Different opacity based on status
    let opacity = 1;
    if (event.resource.status === 'booked') {
        opacity = 0.9;
    } else if (event.resource.status === 'reserved') {
        opacity = 0.7;
    } else if (event.resource.status === 'open') {
        opacity = 0.5;
    }

    return {
        style: {
            backgroundColor: color,
            borderColor: color,
            opacity,
            color: '#fff',
            borderRadius: '4px',
            border: '1px solid',
            padding: '2px 4px',
        },
    };
}

interface DoctorScheduleCalendarProps {
    slots: AvailabilitySlot[];
    currentDate: Date;
    onNavigate: (date: Date, view: View) => void;
    onSelectEvent?: (event: CalendarEvent) => void;
}

/**
 * Calendar component that displays doctor availability slots in a monthly view.
 * Events are color-coded by doctor and show individual time slots.
 */
export function DoctorScheduleCalendar({
    slots,
    currentDate,
    onNavigate,
    onSelectEvent,
}: DoctorScheduleCalendarProps) {
    // Transform slots into calendar events
    const events = useMemo(() => transformSlotsToEvents(slots), [slots]);

    // Handle event click
    const handleSelectEvent = (event: CalendarEvent) => {
        if (onSelectEvent) {
            onSelectEvent(event);
        }
    };

    return (
        <div className="h-[600px] w-full rounded-lg border bg-card p-4">
            <Calendar
                localizer={localizer}
                events={events}
                startAccessor="start"
                endAccessor="end"
                defaultDate={currentDate}
                defaultView="month"
                view="month"
                onNavigate={onNavigate}
                onSelectEvent={handleSelectEvent}
                eventPropGetter={eventStyleGetter}
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

