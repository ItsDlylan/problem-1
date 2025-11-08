import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AppointmentStatus } from '@/types/appointment';
import { Search, SlidersHorizontal } from 'lucide-react';

interface AppointmentFiltersProps {
    onSearch: (term: string) => void;
    onFilterByStatus: (status: AppointmentStatus | 'all') => void;
    onSort: (sortBy: string) => void;
}

export function AppointmentFilters({ onSearch, onFilterByStatus, onSort }: AppointmentFiltersProps) {
    return (
        <div className="flex flex-col gap-4 rounded-lg bg-gray-50/50 p-4 dark:bg-gray-900/50 md:flex-row md:items-center">
            <div className="relative w-full md:w-auto md:flex-grow">
                <Search className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                <Input
                    placeholder="Search by doctor, facility..."
                    className="pl-10"
                    onChange={(e) => onSearch(e.target.value)}
                />
            </div>
            <div className="flex items-center gap-4">
                <Select onValueChange={(value) => onFilterByStatus(value as AppointmentStatus | 'all')}>
                    <SelectTrigger className="w-full md:w-[180px]">
                        <SlidersHorizontal className="mr-2 h-4 w-4" />
                        <SelectValue placeholder="Filter by status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="upcoming">Upcoming</SelectItem>
                        <SelectItem value="complete">Complete</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                        <SelectItem value="no show">No Show</SelectItem>
                    </SelectContent>
                </Select>
                <Select onValueChange={onSort}>
                    <SelectTrigger className="w-full md:w-[180px]">
                        <SelectValue placeholder="Sort by..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="datetime-desc">Date (Newest)</SelectItem>
                        <SelectItem value="datetime-asc">Date (Oldest)</SelectItem>
                        <SelectItem value="doctorName-asc">Doctor Name (A-Z)</SelectItem>
                        <SelectItem value="facilityName-asc">Facility Name (A-Z)</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
}
