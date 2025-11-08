<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to send appointment reminders to patients.
 * This job finds appointments that are scheduled for 24 hours from now
 * and sends reminder notifications to patients who haven't received one yet.
 */
final class SendAppointmentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Finds appointments 24 hours away and sends reminders.
     */
    public function handle(): void
    {
        Log::info('Starting appointment reminder job');

        // Calculate the time window: appointments starting between 23 and 25 hours from now
        // This gives us a 1-hour window to catch appointments that need reminders
        $reminderWindowStart = now()->addHours(23);
        $reminderWindowEnd = now()->addHours(25);

        // Query appointments that:
        // 1. Are scheduled (not cancelled, completed, etc.)
        // 2. Start within the reminder window (24 hours ± 1 hour)
        // 3. Haven't had a reminder sent yet (check meta field)
        $appointments = Appointment::where('status', 'scheduled')
            ->whereBetween('start_at', [$reminderWindowStart, $reminderWindowEnd])
            ->with(['patient', 'facility', 'doctor', 'serviceOffering.service'])
            ->get()
            ->filter(function (Appointment $appointment) {
                // Check if reminder was already sent by looking at meta field
                $meta = $appointment->meta ?? [];
                return ! isset($meta['reminder_sent_at']);
            });

        $remindersSent = 0;

        // Send reminder for each appointment
        foreach ($appointments as $appointment) {
            try {
                // Ensure patient exists and has email
                if (! $appointment->patient || ! $appointment->patient->email) {
                    Log::warning("Skipping appointment {$appointment->id}: patient or email missing", [
                        'appointment_id' => $appointment->id,
                    ]);
                    continue;
                }

                // Send the reminder notification
                $appointment->patient->notify(new AppointmentReminderNotification($appointment));

                // Mark reminder as sent in the appointment's meta field
                $meta = $appointment->meta ?? [];
                $meta['reminder_sent_at'] = now()->toDateTimeString();
                $appointment->meta = $meta;
                $appointment->save();

                $remindersSent++;

                Log::info("Sent reminder for appointment {$appointment->id}", [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'start_at' => $appointment->start_at->toDateTimeString(),
                ]);
            } catch (\Exception $e) {
                // Log error but continue processing other appointments
                Log::error("Failed to send reminder for appointment {$appointment->id}", [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log completion
        Log::info("Appointment reminder job completed. Sent {$remindersSent} reminders", [
            'reminders_sent' => $remindersSent,
            'appointments_checked' => $appointments->count(),
        ]);
    }
}

