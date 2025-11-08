<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AvailabilitySlot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to release expired reservations.
 * This job finds availability slots that are reserved but past their reservation expiry time
 * and releases them back to 'open' status so they can be booked again.
 */
final class ReleaseExpiredReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Finds and releases all expired reservations.
     */
    public function handle(): void
    {
        Log::info('Starting reservation cleanup job');

        // Query slots that are reserved but past their reservation expiry time
        $expiredSlots = AvailabilitySlot::where('status', 'reserved')
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', now())
            ->get();

        $count = $expiredSlots->count();

        if ($count === 0) {
            Log::info('No expired reservations found');
            return;
        }

        // Release all expired reservations
        // Update status to 'open' and clear reserved_until timestamp
        AvailabilitySlot::where('status', 'reserved')
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<', now())
            ->update([
                'status' => 'open',
                'reserved_until' => null,
            ]);

        // Log the number of reservations released
        Log::info("Released {$count} expired reservations", [
            'count' => $count,
        ]);
    }
}

