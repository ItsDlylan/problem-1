/**
 * CalendarFilters component provides filtering controls for the calendar view.
 * Includes doctor filter dropdown and date navigation controls.
 */

import { ChevronLeft, ChevronRight } from 'lucide-react';
import { format, addMonths, subMonths, addWeeks, subWeeks, addDays, subDays, startOfWeek, endOfWeek } from 'date-fns';
import { View } from 'react-big-calendar';

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
    currentView: View;
    onDoctorChange: (doctorId: number | null) => void;
    onDateChange: (date: Date) => void;
    onViewChange: (view: View) => void;
    isLoading?: boolean;
    hideDoctorFilter?: boolean; // Hide doctor filter for doctors (they can only see their own calendar)
}

/**
 * Filter controls for the calendar view.
 * Allows filtering by doctor and navigating between months.
 */
export function CalendarFilters({
    doctors,
    selectedDoctorId,
    currentDate,
    currentView,
    onDoctorChange,
    onDateChange,
    onViewChange,
    isLoading = false,
    hideDoctorFilter = false,
}: CalendarFiltersProps) {
    // Navigate to previous period based on view
    const handlePrevious = () => {
        let newDate: Date;
        switch (currentView) {
            case 'week':
                newDate = subWeeks(currentDate, 1);
                break;
            case 'day':
                newDate = subDays(currentDate, 1);
                break;
            case 'month':
            default:
                newDate = subMonths(currentDate, 1);
                break;
        }
        onDateChange(newDate);
    };

    // Navigate to next period based on view
    const handleNext = () => {
        let newDate: Date;
        switch (currentView) {
            case 'week':
                newDate = addWeeks(currentDate, 1);
                break;
            case 'day':
                newDate = addDays(currentDate, 1);
                break;
            case 'month':
            default:
                newDate = addMonths(currentDate, 1);
                break;
        }
        onDateChange(newDate);
    };

    // Navigate to today
    const handleToday = () => {
        // Create a fresh Date object for today
        const today = new Date();
        // Reset time to start of day to avoid timezone issues
        today.setHours(0, 0, 0, 0);
        onDateChange(today);
    };
    
    // Format date display based on view
    const getDateDisplay = () => {
        switch (currentView) {
            case 'week': {
                const weekStart = startOfWeek(currentDate, { weekStartsOn: 0 });
                const weekEnd = endOfWeek(currentDate, { weekStartsOn: 0 });
                return `${format(weekStart, 'MMM d')} - ${format(weekEnd, 'MMM d, yyyy')}`;
            }
            case 'day':
                return format(currentDate, 'EEEE, MMMM d, yyyy');
            case 'month':
            default:
                return format(currentDate, 'MMMM yyyy');
        }
    };
    
    // Get navigation label based on view
    const getNavigationLabel = () => {
        switch (currentView) {
            case 'week':
                return 'Previous week';
            case 'day':
                return 'Previous day';
            case 'month':
            default:
                return 'Previous month';
        }
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
            {/* Doctor Filter - Hidden for doctors since they can only see their own calendar */}
            {!hideDoctorFilter && (
                <div className="flex items-center gap-2">
                    <label htmlFor="doctor-filter" className="text-sm font-medium text-foreground">
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
            )}
            
            {/* Doctor name display removed - doctors viewing their own calendar don't need to see their name */}

            {/* View Switcher */}
            <div className="flex items-center gap-2">
                <div className="flex rounded-md border border-input bg-background p-1">
                    <Button
                        variant={currentView === 'month' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => onViewChange('month')}
                        disabled={isLoading}
                        className="h-7 px-3 text-xs"
                    >
                        Month
                    </Button>
                    <Button
                        variant={currentView === 'week' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => onViewChange('week')}
                        disabled={isLoading}
                        className="h-7 px-3 text-xs"
                    >
                        Week
                    </Button>
                    <Button
                        variant={currentView === 'day' ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => onViewChange('day')}
                        disabled={isLoading}
                        className="h-7 px-3 text-xs"
                    >
                        Day
                    </Button>
                </div>
            </div>

            {/* Date Navigation */}
            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handlePrevious}
                    disabled={isLoading}
                    aria-label={getNavigationLabel()}
                >
                    <ChevronLeft className="h-4 w-4" />
                </Button>
                <div className="min-w-[200px] text-center">
                    <div className="text-sm font-semibold text-foreground">
                        {getDateDisplay()}
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
                    onClick={handleNext}
                    disabled={isLoading}
                    aria-label={`Next ${currentView}`}
                >
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

