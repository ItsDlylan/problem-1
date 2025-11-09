/**
 * CreateExceptionDialog component displays a modal for creating availability exceptions.
 * Shows when a doctor drags to select days on the calendar.
 */

import { useState } from 'react';
import { format } from 'date-fns';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface CreateExceptionDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    startDate: Date | null;
    endDate: Date | null;
    doctorId: number | null;
    onSubmit: (params: {
        doctor_id: number;
        start_at: string;
        end_at: string;
        type: 'blocked' | 'override' | 'emergency';
        reason?: string;
    }) => Promise<void>;
    isLoading?: boolean;
}

/**
 * Modal dialog for creating availability exceptions.
 * Allows doctors to set a reason for blocking days.
 */
export function CreateExceptionDialog({
    open,
    onOpenChange,
    startDate,
    endDate,
    doctorId,
    onSubmit,
    isLoading = false,
}: CreateExceptionDialogProps) {
    // State for form fields
    const [reason, setReason] = useState('');
    const [type, setType] = useState<'blocked' | 'override' | 'emergency'>('blocked');
    const [error, setError] = useState<string | null>(null);

    // Reset form when dialog closes
    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen) {
            // Reset form when closing
            setReason('');
            setType('blocked');
            setError(null);
        }
        onOpenChange(newOpen);
    };

    // Handle form submission
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        // Validate that we have required data
        if (!startDate || !endDate || !doctorId) {
            setError('Missing required information. Please try again.');
            return;
        }

        try {
            // Format dates as date-only strings (YYYY-MM-DD) to avoid timezone issues
            // Since we're blocking entire days, we don't need time components
            // Use UTC methods to ensure consistent date representation regardless of local timezone
            const startAt = new Date(Date.UTC(
                startDate.getFullYear(),
                startDate.getMonth(),
                startDate.getDate(),
                0, 0, 0, 0
            ));
            
            const endAt = new Date(Date.UTC(
                endDate.getFullYear(),
                endDate.getMonth(),
                endDate.getDate(),
                23, 59, 59, 999
            ));

            await onSubmit({
                doctor_id: doctorId,
                start_at: startAt.toISOString(),
                end_at: endAt.toISOString(),
                type,
                reason: reason.trim() || undefined,
            });

            // Close dialog and reset form on success
            handleOpenChange(false);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to create exception';
            setError(errorMessage);
        }
    };

    // Don't render if we don't have the required dates
    if (!startDate || !endDate || !doctorId) {
        return null;
    }

    // Format date range for display
    const dateRangeText =
        startDate.getTime() === endDate.getTime()
            ? format(startDate, 'EEEE, MMMM d, yyyy')
            : `${format(startDate, 'MMM d')} - ${format(endDate, 'MMM d, yyyy')}`;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Block Availability</DialogTitle>
                    <DialogDescription>
                        Set a reason for blocking availability from {dateRangeText}.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Error message */}
                    {error && (
                        <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                            {error}
                        </div>
                    )}

                    {/* Type selection */}
                    <div className="space-y-2">
                        <Label htmlFor="type">Type</Label>
                        <select
                            id="type"
                            value={type}
                            onChange={(e) => setType(e.target.value as 'blocked' | 'override' | 'emergency')}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="blocked">Blocked</option>
                            <option value="override">Override</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>

                    {/* Reason input */}
                    <div className="space-y-2">
                        <Label htmlFor="reason">Reason (Optional)</Label>
                        <textarea
                            id="reason"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Enter a reason for blocking these days..."
                            rows={4}
                            maxLength={500}
                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <p className="text-xs text-muted-foreground">
                            {reason.length}/500 characters
                        </p>
                    </div>

                    {/* Date range display (read-only) */}
                    <div className="space-y-2">
                        <Label>Date Range</Label>
                        <div className="rounded-md border border-input bg-muted/50 px-3 py-2 text-sm">
                            {dateRangeText}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                            disabled={isLoading}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading ? 'Creating...' : 'Block Days'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

