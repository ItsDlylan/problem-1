import { Appointment, AppointmentStatus } from '@/types/appointment';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import {
    AlertTriangle,
    CalendarCheck,
    CalendarClock,
    CalendarOff,
    CalendarX,
    Clock,    Hospital,
    MapPin,
    Stethoscope,
    Users,
} from 'lucide-react';

interface AppointmentCardProps {
    appointment: Appointment;
}

const statusConfig: Record<
    AppointmentStatus,
    {
        label: string;
        icon: React.ComponentType<{ className?: string }>;
        color: string;
    }
> = {
    upcoming: {
        label: 'Upcoming',
        icon: CalendarClock,
        color: 'bg-blue-500 hover:bg-blue-600',
    },
    complete: {
        label: 'Complete',
        icon: CalendarCheck,
        color: 'bg-green-500 hover:bg-green-600',
    },
    cancelled: {
        label: 'Cancelled',
        icon: CalendarOff,
        color: 'bg-yellow-500 hover:bg-yellow-600 text-black',
    },
    'no show': {
        label: 'No Show',
        icon: CalendarX,
        color: 'bg-red-500 hover:bg-red-600',
    },
};

export function AppointmentCard({ appointment }: AppointmentCardProps) {
    const { icon: StatusIcon, label, color } = statusConfig[appointment.status];

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="text-lg font-semibold">{appointment.doctorName}</CardTitle>
                    <Badge className={cn('text-white', color)}>
                        <StatusIcon className="mr-2 h-4 w-4" />
                        {label}
                    </Badge>
                </div>
                <p className="text-sm text-gray-500">{appointment.serviceCode.description}</p>
            </CardHeader>
            <CardContent className="grid gap-4">
                <div className="flex items-center text-sm">
                    <Hospital className="mr-2 h-4 w-4 text-gray-500" />
                    <span>{appointment.facilityName}</span>
                </div>
                <div className="flex items-center text-sm">
                    <MapPin className="mr-2 h-4 w-4 text-gray-500" />
                    <span>{appointment.facilityLocation}</span>
                </div>
                <div className="flex items-center text-sm">
                    <Clock className="mr-2 h-4 w-4 text-gray-500" />
                    <span>{new Date(appointment.datetime).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })}</span>
                </div>
                <div className="flex items-center justify-between pt-2">
                    <div className="flex items-center text-sm">
                        <Stethoscope className="mr-2 h-4 w-4 text-gray-500" />
                        <span>Service: {appointment.serviceCode.code}</span>
                    </div>
                    {appointment.waitlist > 0 && (
                        <div className="flex items-center text-sm text-orange-500">
                            <Users className="mr-2 h-4 w-4" />
                            <span>Waitlist: {appointment.waitlist}</span>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
