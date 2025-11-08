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
import { Button } from './ui/button';

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
        <Card className="overflow-hidden transition-all duration-300 hover:shadow-lg">
            <div
                className={cn(
                    "flex items-center justify-between p-4 text-white",
                    color,
                )}
            >
                <div className="flex items-center">
                    <StatusIcon className="mr-2 h-5 w-5" />
                    <span className="font-semibold">{label}</span>
                </div>
                <div className="text-sm font-medium">
                    {new Date(appointment.datetime).toLocaleString([], {
                        dateStyle: "medium",
                    })}
                </div>
            </div>
            <CardContent className="p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <CardTitle className="mb-1 text-xl font-bold">
                            {appointment.doctorName}
                        </CardTitle>
                        <p className="text-md font-medium text-gray-500 dark:text-gray-400">
                            {appointment.serviceCode.description}
                        </p>
                    </div>
                    <div className="text-right">
                        <div className="text-lg font-bold text-gray-900 dark:text-gray-50">
                            {new Date(appointment.datetime).toLocaleString(
                                [],
                                {
                                    hour: "2-digit",
                                    minute: "2-digit",
                                },
                            )}
                        </div>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {new Date(appointment.datetime).toLocaleString(
                                [],
                                {
                                    weekday: "long",
                                },
                            )}
                        </p>
                    </div>
                </div>

                <div className="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div className="flex items-center">
                            <Hospital className="mr-2 h-4 w-4 text-gray-500 dark:text-gray-400" />
                            <span className="font-medium text-gray-700 dark:text-gray-300">
                                {appointment.facilityName}
                            </span>
                        </div>
                        <div className="flex items-center">
                            <MapPin className="mr-2 h-4 w-4 text-gray-500 dark:text-gray-400" />
                            <span className="font-medium text-gray-700 dark:text-gray-300">
                                {appointment.facilityLocation}
                            </span>
                        </div>
                        <div className="flex items-center">
                            <Stethoscope className="mr-2 h-4 w-4 text-gray-500 dark:text-gray-400" />
                            <span className="font-medium text-gray-700 dark:text-gray-300">
                                Service: {appointment.serviceCode.code}
                            </span>
                        </div>
                        {appointment.waitlist > 0 && (
                            <div className="flex items-center font-medium text-orange-500">
                                <Users className="mr-2 h-4 w-4" />
                                <span>Waitlist: {appointment.waitlist}</span>
                            </div>
                        )}
                    </div>
                </div>
                {appointment.status === 'upcoming' && (
                    <div className="mt-4 flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <Button variant="outline">Reschedule</Button>
                        <Button variant="destructive">Cancel</Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
