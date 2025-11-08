# Phase 4: Background Jobs
**Priority: MEDIUM - Depends on Phase 1, Can run parallel with Phase 3**
**Estimated Time: 1 day**
**Dependencies: Database migrations must be complete**
**Parallel Execution: Can run alongside Phase 3 tasks**

## Task 4.1: Slot Generation Job
**Duration: 0.5 day**
**Dependencies: Phase 1 complete**
**Blocks: Nothing**

- [ ] **4.1.1** Create `GenerateAvailabilitySlots` job class
  - Location: `app/Jobs/GenerateAvailabilitySlots.php`
  - Implement ShouldQueue interface
  - Properties: facilityId (nullable), doctorId (nullable), startDate, endDate
  - handle() method:
    - Query active availability_rules
    - For each rule, generate slots for date range
    - Check for exceptions (blocked/emergency)
    - Create availability_slots records
    - Use batch insert for performance
    - Log number of slots created

- [ ] **4.1.2** Create job logic for slot generation
  ```php
  // Pseudocode for handle() method
  $rules = AvailabilityRule::active()
    ->when($this->facilityId, fn($q) => $q->where('facility_id', $this->facilityId))
    ->when($this->doctorId, fn($q) => $q->where('doctor_id', $this->doctorId))
    ->get();
  
  foreach ($rules as $rule) {
    $dates = $this->getDatesForDayOfWeek($rule->day_of_week, $this->startDate, $this->endDate);
    foreach ($dates as $date) {
      if ($this->hasException($rule, $date)) continue;
      $slots = $this->generateSlotsForRule($rule, $date);
      AvailabilitySlot::insert($slots);
    }
  }
  ```

- [ ] **4.1.3** Create `GenerateSlotsCommand` artisan command
  - Location: `app/Console/Commands/GenerateSlotsCommand.php`
  - Signature: `slots:generate {--facility=} {--doctor=} {--start-date=} {--end-date=}`
  - Dispatch job with parameters
  - Output success message with slot count

- [ ] **4.1.4** Test slot generation job
  - Create test availability_rule
  - Run job manually: `php artisan slots:generate`
  - Verify slots created in database
  - Verify exceptions block slot creation
  - Verify service-specific rules work

## Task 4.2: Reservation Cleanup Job
**Duration: 0.5 day**
**Dependencies: Phase 1 complete**
**Blocks: Nothing**

- [ ] **4.2.1** Create `ReleaseExpiredReservations` job class
  - Location: `app/Jobs/ReleaseExpiredReservations.php`
  - Implement ShouldQueue interface
  - handle() method:
    - Query slots where status='reserved' AND reserved_until < NOW()
    - Update status to 'open'
    - Set reserved_until = NULL
    - Log number of slots released

- [ ] **4.2.2** Create command for reservation cleanup
  - Location: `app/Console/Commands/ReleaseReservationsCommand.php`
  - Signature: `reservations:release`
  - Dispatch job
  - Output number of released reservations

- [ ] **4.2.3** Test reservation cleanup
  - Manually create reserved slot with expired reserved_until
  - Run command: `php artisan reservations:release`
  - Verify slot status changed to 'open'
  - Verify reserved_until is NULL

## Task 4.3: Schedule Configuration
**Duration: 0.5 day**
**Dependencies: Tasks 4.1 and 4.2 complete**
**Blocks: Nothing**

- [ ] **4.3.1** Configure Laravel scheduler
  - Location: `app/Console/Kernel.php`
  - Schedule slot generation (daily at 1 AM)
  - Schedule reservation cleanup (every 5 minutes)
  ```php
  protected function schedule(Schedule $schedule)
  {
    // Generate slots for next 30 days, daily at 1 AM
    $schedule->job(new GenerateAvailabilitySlots(
      facilityId: null,
      doctorId: null,
      startDate: now(),
      endDate: now()->addDays(30)
    ))->dailyAt('01:00');
    
    // Release expired reservations every 5 minutes
    $schedule->job(new ReleaseExpiredReservations())->everyFiveMinutes();
  }
  ```

- [ ] **4.3.2** Configure queue driver
  - Update `.env`: `QUEUE_CONNECTION=database`
  - Run `php artisan queue:table` (if not exists)
  - Run `php artisan migrate`

- [ ] **4.3.3** Create supervisor configuration
  - For production: create worker process
  - Example: `php artisan queue:work --queue=default --sleep=3 --tries=3`
  - Or use Laravel Forge/Envoyer

- [ ] **4.3.4** Test scheduler
  - Run manually: `php artisan schedule:run`
  - Verify jobs dispatched to queue
  - Run queue worker: `php artisan queue:work`
  - Verify jobs executed
  - Verify slots generated
  - Verify reservations released

## Task 4.4: Monitoring & Logging
**Duration: 0.5 day**
**Dependencies: Tasks 4.1-4.3 complete**

- [ ] **4.4.1** Add logging to slot generation job
  - Log start: "Generating slots for facility X, doctor Y"
  - Log progress: "Created N slots for rule ID Z"
  - Log completion: "Total slots created: N"
  - Use Laravel's Log facade

- [ ] **4.4.2** Add logging to reservation cleanup job
  - Log: "Released N expired reservations"
  - Log errors if any

- [ ] **4.4.3** Create failed job handling
  - Configure queue retries: `--tries=3`
  - Configure failed job table (if not exists)
  - Monitor failed jobs: `php artisan queue:failed`
  - Retry failed jobs: `php artisan queue:retry all`

- [ ] **4.4.4** Create monitoring dashboard (optional for POC)
  - Simple page showing recent job runs
  - Show success/failure counts
  - Show last run times

## Verification Checklist
- [ ] Slot generation job creates correct slots
- [ ] Slot generation respects exceptions
- [ ] Reservation cleanup releases expired slots
- [ ] Scheduler configuration works
- [ ] Queue worker processes jobs
- [ ] Jobs run on schedule
- [ ] Logging works correctly
- [ ] Failed jobs are captured
- [ ] Manual commands work: `php artisan slots:generate` and `php artisan reservations:release`
