<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ReleaseExpiredReservations;
use Illuminate\Console\Command;

/**
 * Artisan command to release expired reservations.
 * This command dispatches the ReleaseExpiredReservations job.
 * Usage: php artisan reservations:release
 */
final class ReleaseReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired reservations (reserved slots past their reservation expiry time)';

    /**
     * Execute the console command.
     * Dispatches the reservation cleanup job.
     */
    public function handle(): int
    {
        $this->info('Starting reservation cleanup...');

        // Dispatch the job
        ReleaseExpiredReservations::dispatch();

        $this->info('Reservation cleanup job dispatched successfully!');
        $this->info('Run "php artisan queue:work" to process the job.');

        return Command::SUCCESS;
    }
}

