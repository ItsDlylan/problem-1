<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySlot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to generate availability slots based on availability rules.
 * This job processes active availability rules and creates time slots for booking.
 * It respects availability exceptions (blocked dates) and handles service-specific rules.
 */
final class GenerateAvailabilitySlots implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int|null $facilityId Optional facility ID to filter rules
     * @param int|null $doctorId Optional doctor ID to filter rules
     * @param CarbonInterface $startDate Start date for slot generation
     * @param CarbonInterface $endDate End date for slot generation
     */
    public function __construct(
        public readonly ?int $facilityId = null,
        public readonly ?int $doctorId = null,
        public readonly CarbonInterface $startDate,
        public readonly CarbonInterface $endDate,
    ) {
    }

    /**
     * Execute the job.
     * Generates availability slots for all active rules within the specified date range.
     */
    public function handle(): void
    {
        // Log the start of slot generation
        $facilityInfo = $this->facilityId ? "facility {$this->facilityId}" : 'all facilities';
        $doctorInfo = $this->doctorId ? "doctor {$this->doctorId}" : 'all doctors';
        Log::info("Generating slots for {$facilityInfo}, {$doctorInfo}", [
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
        ]);

        // Query active availability rules with optional filters
        $rules = AvailabilityRule::active()
            ->when($this->facilityId, fn ($q) => $q->where('facility_id', $this->facilityId))
            ->when($this->doctorId, fn ($q) => $q->where('doctor_id', $this->doctorId))
            ->get();

        $totalSlotsCreated = 0;

        // Process each rule to generate slots
        foreach ($rules as $rule) {
            $slotsCreated = $this->generateSlotsForRule($rule);
            $totalSlotsCreated += $slotsCreated;

            // Log progress for each rule
            Log::info("Created {$slotsCreated} slots for rule ID {$rule->id}", [
                'rule_id' => $rule->id,
                'facility_id' => $rule->facility_id,
                'doctor_id' => $rule->doctor_id,
            ]);
        }

        // Log completion with total slots created
        Log::info("Slot generation completed. Total slots created: {$totalSlotsCreated}", [
            'total_slots' => $totalSlotsCreated,
            'rules_processed' => $rules->count(),
        ]);
    }

    /**
     * Generate slots for a specific availability rule.
     * This method handles date calculation, exception checking, and slot creation.
     *
     * @param AvailabilityRule $rule The availability rule to process
     * @return int Number of slots created for this rule
     */
    private function generateSlotsForRule(AvailabilityRule $rule): int
    {
        // Get all dates that match the rule's day of week within the date range
        $dates = $this->getDatesForDayOfWeek($rule->day_of_week, $this->startDate, $this->endDate);

        $slotsToInsert = [];

        // Process each matching date
        foreach ($dates as $date) {
            // Skip this date if there's an exception (blocked or emergency)
            if ($this->hasException($rule, $date)) {
                continue;
            }

            // Generate slots for this date based on the rule's time window
            $dateSlots = $this->generateSlotsForDate($rule, $date);
            $slotsToInsert = array_merge($slotsToInsert, $dateSlots);
        }

        // Filter out slots that already exist to prevent duplicates
        // Check for existing slots with the same facility, doctor, start_at, and end_at
        if (! empty($slotsToInsert)) {
            // Get all existing slots for this doctor/facility in the date range
            $dateRangeStart = min(array_column($slotsToInsert, 'start_at'));
            $dateRangeEnd = max(array_column($slotsToInsert, 'end_at'));
            
            $existingSlots = AvailabilitySlot::where('facility_id', $rule->facility_id)
                ->where('doctor_id', $rule->doctor_id)
                ->whereBetween('start_at', [$dateRangeStart, $dateRangeEnd])
                ->get()
                ->keyBy(function ($slot) {
                    // Use formatted datetime string for comparison
                    return Carbon::parse($slot->start_at)->format('Y-m-d H:i:s') . '|' . 
                           Carbon::parse($slot->end_at)->format('Y-m-d H:i:s');
                });

            // Filter out slots that already exist
            $slotsToInsert = array_filter($slotsToInsert, function ($slot) use ($existingSlots) {
                $key = Carbon::parse($slot['start_at'])->format('Y-m-d H:i:s') . '|' . 
                       Carbon::parse($slot['end_at'])->format('Y-m-d H:i:s');
                return ! $existingSlots->has($key);
            });

            // Re-index array after filtering
            $slotsToInsert = array_values($slotsToInsert);
        }

        // Batch insert all new slots for performance
        if (! empty($slotsToInsert)) {
            // Use chunking for very large batches to avoid memory issues
            $chunks = array_chunk($slotsToInsert, 500);
            foreach ($chunks as $chunk) {
                AvailabilitySlot::insert($chunk);
            }
        }

        return count($slotsToInsert);
    }

    /**
     * Get all dates that match a specific day of week within a date range.
     * Day of week: 0=Sunday, 1=Monday, ..., 6=Saturday
     *
     * @param int $dayOfWeek The day of week (0-6)
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array<Carbon> Array of matching dates
     */
    private function getDatesForDayOfWeek(int $dayOfWeek, Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];
        $currentDate = $startDate->copy()->startOfDay();

        // Find the first occurrence of the target day of week
        while ($currentDate->dayOfWeek !== $dayOfWeek && $currentDate->lte($endDate)) {
            $currentDate->addDay();
        }

        // Collect all matching dates within the range
        while ($currentDate->lte($endDate)) {
            $dates[] = $currentDate->copy();
            $currentDate->addWeek(); // Move to next week's same day
        }

        return $dates;
    }

    /**
     * Check if there's an availability exception for a rule on a specific date.
     * Exceptions can block entire date ranges or specific time periods.
     *
     * @param AvailabilityRule $rule The availability rule
     * @param Carbon $date The date to check
     * @return bool True if there's an exception that blocks this date
     */
    private function hasException(AvailabilityRule $rule, Carbon $date): bool
    {
        $dateStart = $date->copy()->startOfDay();
        $dateEnd = $date->copy()->endOfDay();

        // Check for exceptions that overlap with this date
        // Exceptions can be rule-specific, facility-specific, or doctor-specific
        $hasException = AvailabilityException::where(function ($query) use ($rule, $dateStart, $dateEnd) {
            // Rule-specific exception
            $query->where('availability_rule_id', $rule->id)
                // Or facility/doctor match
                ->orWhere(function ($q) use ($rule, $dateStart, $dateEnd) {
                    $q->where('facility_id', $rule->facility_id)
                        ->where('doctor_id', $rule->doctor_id)
                        ->where(function ($subQ) use ($dateStart, $dateEnd) {
                            // Exception overlaps with the date
                            $subQ->whereBetween('start_at', [$dateStart, $dateEnd])
                                ->orWhereBetween('end_at', [$dateStart, $dateEnd])
                                ->orWhere(function ($overlapQ) use ($dateStart, $dateEnd) {
                                    $overlapQ->where('start_at', '<=', $dateStart)
                                        ->where('end_at', '>=', $dateEnd);
                                });
                        });
                });
        })->exists();

        return $hasException;
    }

    /**
     * Generate time slots for a specific date based on an availability rule.
     * Creates slots from start_time to end_time with the specified duration and interval.
     *
     * @param AvailabilityRule $rule The availability rule
     * @param Carbon $date The date to generate slots for
     * @return array<array<string, mixed>> Array of slot data ready for batch insert
     */
    private function generateSlotsForDate(AvailabilityRule $rule, Carbon $date): array
    {
        $slots = [];

        // Parse start and end times from the rule
        $startTime = Carbon::parse($rule->start_time);
        $endTime = Carbon::parse($rule->end_time);

        // Combine date with start time
        $slotStart = $date->copy()
            ->setTime($startTime->hour, $startTime->minute, 0);

        // Calculate slot duration and interval
        $slotDurationMinutes = $rule->slot_duration_minutes;
        $slotIntervalMinutes = $rule->slot_interval_minutes ?? $slotDurationMinutes;

        // Generate slots until we reach or exceed the end time
        while ($slotStart->copy()->addMinutes($slotDurationMinutes)->lte($date->copy()->setTime($endTime->hour, $endTime->minute, 0))) {
            $slotEnd = $slotStart->copy()->addMinutes($slotDurationMinutes);

            // Create slot data array
            $slots[] = [
                'facility_id' => $rule->facility_id,
                'doctor_id' => $rule->doctor_id,
                'service_offering_id' => $rule->service_offering_id,
                'start_at' => $slotStart->toDateTimeString(),
                'end_at' => $slotEnd->toDateTimeString(),
                'status' => 'open',
                'capacity' => 1, // Default capacity, can be customized per rule if needed
                'reserved_until' => null,
                'created_from_rule_id' => $rule->id,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];

            // Move to next slot start time
            $slotStart->addMinutes($slotIntervalMinutes);
        }

        return $slots;
    }
}

