<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateAvailabilitySlots;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Artisan command to generate availability slots.
 * This command dispatches the GenerateAvailabilitySlots job with optional filters.
 * Usage: php artisan slots:generate [--facility=ID] [--doctor=ID] [--start-date=DATE] [--end-date=DATE]
 */
final class GenerateSlotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slots:generate 
                            {--facility= : Filter by facility ID}
                            {--doctor= : Filter by doctor ID}
                            {--start-date= : Start date for slot generation (Y-m-d format, defaults to today)}
                            {--end-date= : End date for slot generation (Y-m-d format, defaults to 30 days from start)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate availability slots based on active availability rules';

    /**
     * Execute the console command.
     * Parses command options and dispatches the slot generation job.
     */
    public function handle(): int
    {
        $this->info('Starting slot generation...');

        // Parse facility ID if provided
        $facilityId = $this->option('facility') ? (int) $this->option('facility') : null;
        if ($facilityId !== null) {
            $this->info("Filtering by facility ID: {$facilityId}");
        }

        // Parse doctor ID if provided
        $doctorId = $this->option('doctor') ? (int) $this->option('doctor') : null;
        if ($doctorId !== null) {
            $this->info("Filtering by doctor ID: {$doctorId}");
        }

        // Parse start date or default to today
        $startDate = $this->option('start-date')
            ? Carbon::parse($this->option('start-date'))
            : Carbon::today();

        // Parse end date or default to 30 days from start
        $endDate = $this->option('end-date')
            ? Carbon::parse($this->option('end-date'))
            : $startDate->copy()->addDays(30);

        $this->info("Generating slots from {$startDate->toDateString()} to {$endDate->toDateString()}");

        // Dispatch the job
        GenerateAvailabilitySlots::dispatch(
            facilityId: $facilityId,
            doctorId: $doctorId,
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->info('Slot generation job dispatched successfully!');
        $this->info('Run "php artisan queue:work" to process the job.');

        return Command::SUCCESS;
    }
}

