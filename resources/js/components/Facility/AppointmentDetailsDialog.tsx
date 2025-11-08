/**
 * AppointmentDetailsDialog component displays detailed information about an appointment/slot.
 * Shows when user clicks on a calendar event.
 */

import { format } from 'date-fns';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import type { CalendarEvent } from '@/types/facility';

interface AppointmentDetailsDialogProps {
    event: CalendarEvent | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
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

export function AppointmentDetailsDialog({
    event,
    open,
    onOpenChange,
}: AppointmentDetailsDialogProps) {
    if (!event) {
        return null;
    }

    const slot = event.resource.slot;
    const appointments = slot?.appointments || [];
    const doctor = slot?.doctor;
    const serviceOffering = slot?.service_offering;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Appointment Details</DialogTitle>
                    <DialogDescription>
                        View detailed information about this appointment slot
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Status Badge */}
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium">Status:</span>
                        <Badge variant={getStatusVariant(event.resource.status)}>
                            {formatStatus(event.resource.status)}
                        </Badge>
                    </div>

                    {/* Date and Time */}
                    <div className="space-y-2">
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Date:</span>
                            <p className="text-sm">{format(event.start, 'EEEE, MMMM d, yyyy')}</p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Time:</span>
                            <p className="text-sm">
                                {format(event.start, 'h:mm a')} - {format(event.end, 'h:mm a')}
                            </p>
                        </div>
                    </div>

                    {/* Doctor Information */}
                    {doctor && (
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Doctor:</span>
                            <p className="text-sm">
                                {doctor.display_name}
                                {doctor.specialty && ` - ${doctor.specialty}`}
                            </p>
                        </div>
                    )}

                    {/* Service Information */}
                    {serviceOffering && (
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">Service:</span>
                            <p className="text-sm">Service ID: {serviceOffering.service_id}</p>
                        </div>
                    )}

                    {/* Appointments */}
                    {appointments.length > 0 ? (
                        <div className="space-y-2">
                            <span className="text-sm font-medium text-muted-foreground">
                                Appointments ({appointments.length}):
                            </span>
                            <div className="space-y-2 rounded-lg border bg-muted/50 p-3">
                                {appointments.map((appointment) => (
                                    <div
                                        key={appointment.id}
                                        className="space-y-2 rounded border bg-background p-3"
                                    >
                                        <div className="flex items-center justify-between">
                                            <Badge variant={getStatusVariant(appointment.status)}>
                                                {formatStatus(appointment.status)}
                                            </Badge>
                                            <span className="text-xs text-muted-foreground">
                                                ID: {appointment.id}
                                            </span>
                                        </div>
                                        
                                        {/* Patient Information */}
                                        {appointment.patient && (
                                            <div className="space-y-1 border-t pt-2">
                                                <div>
                                                    <span className="text-xs font-medium text-muted-foreground">
                                                        Patient:
                                                    </span>
                                                    <p className="text-sm">
                                                        {appointment.patient.first_name}{' '}
                                                        {appointment.patient.last_name}
                                                    </p>
                                                </div>
                                                {appointment.patient.email && (
                                                    <div>
                                                        <span className="text-xs font-medium text-muted-foreground">
                                                            Email:
                                                        </span>
                                                        <p className="text-xs text-muted-foreground">
                                                            {appointment.patient.email}
                                                        </p>
                                                    </div>
                                                )}
                                                {appointment.patient.phone && (
                                                    <div>
                                                        <span className="text-xs font-medium text-muted-foreground">
                                                            Phone:
                                                        </span>
                                                        <p className="text-xs text-muted-foreground">
                                                            {appointment.patient.phone}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                        
                                        {appointment.notes && (
                                            <div className="border-t pt-2">
                                                <span className="text-xs font-medium text-muted-foreground">
                                                    Notes:
                                                </span>
                                                <p className="text-xs text-muted-foreground mt-1">
                                                    {appointment.notes}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : (
                        <div>
                            <span className="text-sm font-medium text-muted-foreground">
                                Appointments:
                            </span>
                            <p className="text-sm text-muted-foreground">No appointments booked</p>
                        </div>
                    )}

                    {/* Slot Information */}
                    {slot && (
                        <div className="space-y-2 rounded-lg border bg-muted/50 p-3">
                            <span className="text-sm font-medium text-muted-foreground">
                                Slot Information:
                            </span>
                            <div className="space-y-1 text-xs text-muted-foreground">
                                <p>Slot ID: {slot.id}</p>
                                <p>Capacity: {slot.capacity}</p>
                                {slot.reserved_until && (
                                    <p>
                                        Reserved until:{' '}
                                        {format(new Date(slot.reserved_until), 'MMM d, yyyy h:mm a')}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

