/**
 * EditExceptionDialog component displays a modal for editing or deleting availability exceptions.
 * Shows when a doctor clicks on a blocked/cancelled period (exception) on the calendar.
 */

import { useState, useEffect } from 'react';
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
import { Label } from '@/components/ui/label';
import type { AvailabilityException, UpdateAvailabilityExceptionParams } from '@/types/facility';

interface EditExceptionDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    exception: AvailabilityException | null;
    onUpdate: (id: number, params: UpdateAvailabilityExceptionParams) => Promise<void>;
    onDelete: (id: number) => Promise<void>;
    isLoading?: boolean;
}

/**
 * Modal dialog for editing or deleting availability exceptions.
 * Allows doctors to update the reason/type or delete (undo) the exception.
 */
export function EditExceptionDialog({
    open,
    onOpenChange,
    exception,
    onUpdate,
    onDelete,
    isLoading = false,
}: EditExceptionDialogProps) {
    // State for form fields
    const [reason, setReason] = useState('');
    const [type, setType] = useState<'blocked' | 'override' | 'emergency'>('blocked');
    const [error, setError] = useState<string | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Initialize form fields when exception changes
    useEffect(() => {
        if (exception) {
            setReason(exception.meta?.reason || '');
            setType(exception.type);
            setError(null);
        }
    }, [exception]);

    // Reset form when dialog closes
    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen) {
            // Reset form when closing
            setReason('');
            setType('blocked');
            setError(null);
            setIsDeleting(false);
        }
        onOpenChange(newOpen);
    };

    // Handle form submission (update)
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        if (!exception) {
            setError('Exception not found. Please try again.');
            return;
        }

        try {
            await onUpdate(exception.id, {
                type,
                reason: reason.trim() || undefined,
            });

            // Close dialog on success
            handleOpenChange(false);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to update exception';
            setError(errorMessage);
        }
    };

    // Handle delete (undo blocking)
    const handleDelete = async () => {
        if (!exception) {
            setError('Exception not found. Please try again.');
            return;
        }

        // Confirm deletion
        if (!confirm('Are you sure you want to remove this block? This will make these days available again.')) {
            return;
        }

        setIsDeleting(true);
        setError(null);

        try {
            await onDelete(exception.id);

            // Close dialog on success
            handleOpenChange(false);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Failed to delete exception';
            setError(errorMessage);
        } finally {
            setIsDeleting(false);
        }
    };

    // Don't render if we don't have an exception
    if (!exception) {
        return null;
    }

    // Format date range for display
    const startDate = new Date(exception.start_at);
    const endDate = new Date(exception.end_at);
    const dateRangeText =
        startDate.getTime() === endDate.getTime()
            ? format(startDate, 'EEEE, MMMM d, yyyy')
            : `${format(startDate, 'MMM d')} - ${format(endDate, 'MMM d, yyyy')}`;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Blocked Availability</DialogTitle>
                    <DialogDescription>
                        Update the reason or remove the block for {dateRangeText}.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Error message */}
                    {error && (
                        <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                            {error}
                        </div>
                    )}

                    {/* Date range display (read-only) */}
                    <div className="space-y-2">
                        <Label>Date Range</Label>
                        <div className="rounded-md border border-input bg-muted/50 px-3 py-2 text-sm">
                            {dateRangeText}
                        </div>
                    </div>

                    {/* Type selection */}
                    <div className="space-y-2">
                        <Label htmlFor="type">Type</Label>
                        <select
                            id="type"
                            value={type}
                            onChange={(e) => setType(e.target.value as 'blocked' | 'override' | 'emergency')}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            disabled={isLoading || isDeleting}
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
                            disabled={isLoading || isDeleting}
                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <p className="text-xs text-muted-foreground">
                            {reason.length}/500 characters
                        </p>
                    </div>

                    <DialogFooter className="flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                        {/* Delete button on the left */}
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={isLoading || isDeleting}
                        >
                            {isDeleting ? 'Removing...' : 'Remove Block'}
                        </Button>

                        {/* Update and Cancel buttons on the right */}
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => handleOpenChange(false)}
                                disabled={isLoading || isDeleting}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isLoading || isDeleting}>
                                {isLoading ? 'Updating...' : 'Update'}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

