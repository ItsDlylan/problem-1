<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendAppointmentReminders;
use Illuminate\Console\Command;

/**
 * Artisan command to send appointment reminders.
 * This command dispatches the SendAppointmentReminders job.
 * Usage: php artisan reminders:send
 */
final class SendRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment reminders to patients (for appointments 24 hours away)';

    /**
     * Execute the console command.
     * Dispatches the appointment reminder job.
     */
    public function handle(): int
    {
        $this->info('Starting appointment reminder job...');

        // Dispatch the job
        SendAppointmentReminders::dispatch();

        $this->info('Appointment reminder job dispatched successfully!');
        $this->info('Run "php artisan queue:work" to process the job.');

        return Command::SUCCESS;
    }
}

