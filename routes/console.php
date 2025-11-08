<?php

declare(strict_types=1);

use App\Jobs\GenerateAvailabilitySlots;
use App\Jobs\ReleaseExpiredReservations;
use App\Jobs\SendAppointmentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Configure scheduled tasks for background jobs
Schedule::job(new GenerateAvailabilitySlots(
    facilityId: null,
    doctorId: null,
    startDate: now(),
    endDate: now()->addDays(30),
))->dailyAt('01:00')
    ->name('generate-availability-slots')
    ->description('Generate availability slots for the next 30 days');

// Release expired reservations every 5 minutes
Schedule::job(new ReleaseExpiredReservations())
    ->everyFiveMinutes()
    ->name('release-expired-reservations')
    ->description('Release expired reservations (reserved slots past their reservation expiry time)');

// Send appointment reminders hourly (checks for appointments 24 hours away)
Schedule::job(new SendAppointmentReminders())
    ->hourly()
    ->name('send-appointment-reminders')
    ->description('Send appointment reminders to patients (for appointments 24 hours away)');
