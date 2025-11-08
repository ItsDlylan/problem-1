/**
 * CalendarFilters component provides filtering controls for the calendar view.
 * Includes doctor filter dropdown and date navigation controls.
 */

import { ChevronLeft, ChevronRight } from 'lucide-react';
import { format, startOfMonth, endOfMonth, addMonths, subMonths } from 'date-fns';

import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Doctor } from '@/types/facility';

interface CalendarFiltersProps {
    doctors: Doctor[];
    selectedDoctorId: number | null;
    currentDate: Date;
    onDoctorChange: (doctorId: number | null) => void;
    onDateChange: (date: Date) => void;
    isLoading?: boolean;
}

/**
 * Filter controls for the calendar view.
 * Allows filtering by doctor and navigating between months.
 */
export function CalendarFilters({
    doctors,
    selectedDoctorId,
    currentDate,
    onDoctorChange,
    onDateChange,
    isLoading = false,
}: CalendarFiltersProps) {
    // Navigate to previous month
    const handlePreviousMonth = () => {
        const newDate = subMonths(currentDate, 1);
        onDateChange(newDate);
    };

    // Navigate to next month
    const handleNextMonth = () => {
        const newDate = addMonths(currentDate, 1);
        onDateChange(newDate);
    };

    // Navigate to today
    const handleToday = () => {
        onDateChange(new Date());
    };

    // Handle doctor filter change
    const handleDoctorChange = (value: string) => {
        if (value === 'all') {
            onDoctorChange(null);
        } else {
            onDoctorChange(parseInt(value, 10));
        }
    };

    return (
        <div className="flex flex-col gap-4 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between">
            {/* Doctor Filter */}
            <div className="flex items-center gap-2">
                <label htmlFor="doctor-filter" className="text-sm font-medium">
                    Filter by Doctor:
                </label>
                <Select
                    value={selectedDoctorId?.toString() || 'all'}
                    onValueChange={handleDoctorChange}
                    disabled={isLoading}
                >
                    <SelectTrigger id="doctor-filter" className="w-[200px]">
                        <SelectValue placeholder="All Doctors" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Doctors</SelectItem>
                        {doctors.map((doctor) => (
                            <SelectItem key={doctor.id} value={doctor.id.toString()}>
                                {doctor.display_name}
                                {doctor.specialty && ` - ${doctor.specialty}`}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {/* Date Navigation */}
            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handlePreviousMonth}
                    disabled={isLoading}
                    aria-label="Previous month"
                >
                    <ChevronLeft className="h-4 w-4" />
                </Button>
                <div className="min-w-[180px] text-center">
                    <div className="text-sm font-semibold">
                        {format(currentDate, 'MMMM yyyy')}
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={handleToday}
                        disabled={isLoading}
                        className="h-auto p-1 text-xs"
                    >
                        Go to Today
                    </Button>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handleNextMonth}
                    disabled={isLoading}
                    aria-label="Next month"
                >
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

