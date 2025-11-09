import * as React from 'react';

import { Appointment, AppointmentStatus } from '@/types/appointment';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import {
    CalendarCheck,
    CalendarClock,
    CalendarOff,
    CalendarX,
    Hospital,
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
        headerClass: string;
        rippleRgb: string;
    }
> = {
    upcoming: {
        label: 'Upcoming',
        icon: CalendarClock,
        headerClass:
            'bg-blue-200 text-blue-900 dark:bg-blue-500/30 dark:text-blue-100',
        rippleRgb: '59, 130, 246',
    },
    complete: {
        label: 'Complete',
        icon: CalendarCheck,
        headerClass:
            'bg-green-200 text-green-900 dark:bg-green-500/30 dark:text-green-100',
        rippleRgb: '34, 197, 94',
    },
    cancelled: {
        label: 'Cancelled',
        icon: CalendarOff,
        headerClass:
            'bg-yellow-200 text-yellow-900 dark:bg-yellow-500/30 dark:text-yellow-100',
        rippleRgb: '234, 179, 8',
    },
    'no show': {
        label: 'No Show',
        icon: CalendarX,
        headerClass:
            'bg-red-200 text-red-900 dark:bg-red-500/30 dark:text-red-100',
        rippleRgb: '239, 68, 68',
    },
};

export function AppointmentCard({ appointment }: AppointmentCardProps) {
    const { icon: StatusIcon, label, headerClass, rippleRgb } =
        statusConfig[appointment.status];
    const statusBarRef = React.useRef<HTMLDivElement>(null);
    const animationFrameRef = React.useRef<number | null>(null);

    const stopAnimation = React.useCallback(() => {
        if (animationFrameRef.current !== null) {
            cancelAnimationFrame(animationFrameRef.current);
            animationFrameRef.current = null;
        }
    }, []);

    const animateOpacity = React.useCallback(
        (targetOpacity: number, duration = 350) => {
            if (!statusBarRef.current) {
                return;
            }

            const element = statusBarRef.current;
            const startingOpacity = parseFloat(
                element.style.getPropertyValue('--cursor-opacity') || '0',
            );
            const difference = targetOpacity - startingOpacity;
            const startTime = performance.now();

            stopAnimation();

            const easeOutCubic = (t: number) => 1 - Math.pow(1 - t, 3);

            const step = (timestamp: number) => {
                if (!statusBarRef.current) {
                    animationFrameRef.current = null;
                    return;
                }

                const elapsed = timestamp - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const eased = easeOutCubic(progress);
                const nextValue = startingOpacity + difference * eased;

                element.style.setProperty(
                    '--cursor-opacity',
                    nextValue.toFixed(3),
                );

                if (progress < 1) {
                    animationFrameRef.current = requestAnimationFrame(step);
                } else {
                    animationFrameRef.current = null;
                    if (targetOpacity === 0) {
                        element.style.setProperty('--cursor-x', '50%');
                        element.style.setProperty('--cursor-y', '50%');
                    }
                }
            };

            animationFrameRef.current = requestAnimationFrame(step);
        },
        [stopAnimation],
    );

    React.useEffect(
        () => () => {
            stopAnimation();
        },
        [stopAnimation],
    );

    const handleMouseMove = (event: React.MouseEvent<HTMLDivElement>) => {
        if (!statusBarRef.current) {
            return;
        }

        stopAnimation();

        const rect = statusBarRef.current.getBoundingClientRect();
        const xPercent = ((event.clientX - rect.left) / rect.width) * 100;
        const yPercent = ((event.clientY - rect.top) / rect.height) * 100;

        statusBarRef.current.style.setProperty('--cursor-x', `${xPercent}%`);
        statusBarRef.current.style.setProperty('--cursor-y', `${yPercent}%`);
        statusBarRef.current.style.setProperty('--cursor-opacity', '0.45');
    };

    const handleMouseLeave = () => {
        if (!statusBarRef.current) {
            return;
        }

        animateOpacity(0);
    };

    const statusBarStyle = React.useMemo(
        () =>
            ({
                '--cursor-x': '50%',
                '--cursor-y': '50%',
                '--cursor-opacity': '0',
                backgroundImage: `radial-gradient(circle at var(--cursor-x, 50%) var(--cursor-y, 50%), rgba(${rippleRgb}, var(--cursor-opacity, 0.0)) 0%, rgba(${rippleRgb}, 0) 60%)`,
            }) as React.CSSProperties & {
                '--cursor-x': string;
                '--cursor-y': string;
                '--cursor-opacity': string;
            },
        [rippleRgb],
    );

    return (
        <Card className="overflow-hidden transition-all duration-300 hover:shadow-lg">
            <div
                ref={statusBarRef}
                onMouseMove={handleMouseMove}
                onMouseLeave={handleMouseLeave}
                className={cn(
                    'relative flex items-center justify-between p-4 transition-colors duration-500 ease-out',
                    headerClass,
                )}
                style={statusBarStyle}
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
                        <Button className="bg-black text-white hover:bg-gray-900 focus-visible:ring-gray-500">
                            Cancel
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
