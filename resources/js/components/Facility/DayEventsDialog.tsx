/**
 * DayEventsDialog component displays all appointments/events for a selected day.
 * Shows when user clicks on a day cell in the calendar (similar to "+X more" functionality).
 */

import { format } from 'date-fns';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import type { CalendarEvent } from '@/types/facility';

interface DayEventsDialogProps {
    events: CalendarEvent[];
    selectedDate: Date | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onEventClick: (event: CalendarEvent) => void;
    isOwnCalendar?: boolean; // If true, hide doctor information (doctor viewing their own calendar)
}

/**
 * Get status badge variant based on status
 */
function getStatusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'booked':
        case 'completed':
        case 'checked_in':
        case 'in_progress':
            return 'default';
        case 'reserved':
            return 'secondary';
        case 'no_show':
        case 'cancelled':
            return 'destructive';
        case 'open':
        case 'scheduled':
        default:
            return 'outline';
    }
}

/**
 * Format status text for display
 */
function formatStatus(status: string): string {
    return status
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

/**
 * Get color based on status for the time badge
 */
function getStatusColor(status: string): string {
    switch (status) {
        case 'booked':
        case 'completed':
        case 'checked_in':
        case 'in_progress':
            return 'bg-green-500';
        case 'reserved':
            return 'bg-yellow-500';
        case 'open':
        case 'scheduled':
            return 'bg-blue-500';
        case 'cancelled':
            return 'bg-gray-500';
        case 'no_show':
            return 'bg-red-500';
        case 'blocked':
            return 'bg-gray-600';
        default:
            return 'bg-gray-500';
    }
}

export function DayEventsDialog({
    events,
    selectedDate,
    open,
    onOpenChange,
    onEventClick,
    isOwnCalendar = false,
}: DayEventsDialogProps) {
    if (!selectedDate || events.length === 0) {
        return null;
    }

    // Sort events by start time
    const sortedEvents = [...events].sort((a, b) => 
        a.start.getTime() - b.start.getTime()
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        Appointments for {format(selectedDate, 'EEEE, MMMM d, yyyy')}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-3">
                    {sortedEvents.length === 0 ? (
                        <p className="text-sm text-muted-foreground text-center py-4">
                            No appointments scheduled for this day.
                        </p>
                    ) : (
                        sortedEvents.map((event) => {
                            const slot = event.resource.slot;
                            const appointments = slot?.appointments || [];
                            const doctor = slot?.doctor;
                            const serviceOffering = slot?.service_offering;
                            const isException = event.resource.status === 'blocked' && event.resource.exceptionId;

                            return (
                                <div
                                    key={event.id}
                                    className="rounded-lg border bg-card p-4 hover:bg-accent/50 transition-colors cursor-pointer"
                                    onClick={() => {
                                        onEventClick(event);
                                        onOpenChange(false);
                                    }}
                                >
                                    <div className="flex items-start justify-between gap-4">
                                        {/* Left side: Time and main info */}
                                        <div className="flex-1 space-y-2">
                                            {/* Time badge */}
                                            <div className="flex items-center gap-2">
                                                <div className={`h-2 w-2 rounded-full ${getStatusColor(event.resource.status)}`} />
                                                <span className="text-sm font-medium">
                                                    {format(event.start, 'h:mm a')} - {format(event.end, 'h:mm a')}
                                                </span>
                                                <Badge variant={getStatusVariant(event.resource.status)} className="text-xs">
                                                    {formatStatus(event.resource.status)}
                                                </Badge>
                                            </div>

                                            {/* Title/Patient name or Doctor name */}
                                            <div className="font-semibold text-base">
                                                {event.title}
                                            </div>

                                            {/* Doctor information - Hide if viewing own calendar */}
                                            {doctor && !isOwnCalendar && !isException && (
                                                <div className="text-sm text-muted-foreground">
                                                    {doctor.display_name}
                                                    {doctor.specialty && ` - ${doctor.specialty}`}
                                                </div>
                                            )}

                                            {/* Service Information */}
                                            {serviceOffering && !isException && (
                                                <div className="text-sm text-muted-foreground">
                                                    {serviceOffering.service?.name || `Service ID: ${serviceOffering.service_id}`}
                                                </div>
                                            )}

                                            {/* Exception reason */}
                                            {isException && event.resource.exception && (
                                                <div className="text-sm text-muted-foreground">
                                                    {event.resource.exception.meta?.reason || 'Blocked period'}
                                                </div>
                                            )}

                                            {/* Appointment count if multiple */}
                                            {appointments.length > 1 && (
                                                <div className="text-xs text-muted-foreground">
                                                    {appointments.length} appointment{appointments.length > 1 ? 's' : ''}
                                                </div>
                                            )}
                                        </div>

                                        {/* Right side: Click indicator */}
                                        <div className="text-xs text-muted-foreground self-center">
                                            Click to view details →
                                        </div>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

