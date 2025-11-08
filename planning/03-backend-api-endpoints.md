# Phase 3: Backend API Endpoints
**Priority: HIGH - Depends on Phase 2, Blocks Phase 5**
**Estimated Time: 3-4 days**
**Dependencies: Authentication system must be complete**
**Parallel Execution: Tasks 3.1, 3.2, 3.3, 3.4 can run in parallel**

## Task 3.1: Patient API Endpoints
**Duration: 2 days**
**Dependencies: Phase 2 complete**
**Blocks: Task 5.1 (Patient Dashboard)**

### Day 1: Core Patient Endpoints
- [ ] **3.1.1** Create `PatientAppointmentController`
  - Location: `app/Http/Controllers/Api/Patient/PatientAppointmentController.php`
  - Middleware: `auth:patient`
  - Methods:
    - `index()` - GET /api/patient/appointments (list patient's appointments)
    - `show($appointment)` - GET /api/patient/appointments/{id}
    - `store()` - POST /api/patient/appointments (book new appointment)
    - `cancel($appointment)` - POST /api/patient/appointments/{id}/cancel

- [ ] **3.1.2** Create `PatientProfileController`
  - Location: `app/Http/Controllers/Api/Patient/PatientProfileController.php`
  - Middleware: `auth:patient`
  - Methods:
    - `show()` - GET /api/patient/profile
    - `update()` - PUT /api/patient/profile

- [ ] **3.1.3** Create `SlotController` (for available slots)
  - Location: `app/Http/Controllers/Api/Patient/SlotController.php`
  - Middleware: `auth:patient`
  - Methods:
    - `available()` - GET /api/patient/available-slots
      - Query params: facility_id, service_id, doctor_id, date
      - Returns: array of available slots

- [ ] **3.1.4** Create `FacilityController` (for patient view)
  - Location: `app/Http/Controllers/Api/Patient/FacilityController.php`
  - Middleware: `auth:patient`
  - Methods:
    - `index()` - GET /api/patient/facilities (list all facilities)

- [ ] **3.1.5** Create `ServiceController` (for patient view)
  - Location: `app/Http/Controllers/Api/Patient/ServiceController.php`
  - Middleware: `auth:patient`
  - Methods:
    - `index()` - GET /api/patient/services (list services for facility)
      - Query param: facility_id

### Day 2: Form Requests & Business Logic
- [ ] **3.1.6** Create `BookAppointmentRequest`
  - Location: `app/Http/Requests/Patient/BookAppointmentRequest.php`
  - Validation: slot_id (exists and is open), service_offering_id, notes (optional)
  - Authorization: patient can only book for themselves

- [ ] **3.1.7** Create `CancelAppointmentRequest`
  - Location: `app/Http/Requests/Patient/CancelAppointmentRequest.php`
  - Validation: appointment_id (exists and belongs to patient)
  - Authorization: patient can only cancel own appointments

- [ ] **3.1.8** Implement appointment booking logic
  - Reserve slot (mark as reserved, set reserved_until)
  - Create appointment record
  - Create appointment_steps from workflow
  - Mark slot as booked
  - Return appointment with steps

- [ ] **3.1.9** Implement appointment cancellation logic
  - Update appointment status to 'cancelled'
  - Update slot status back to 'open'
  - Cancel all appointment_steps

- [ ] **3.1.10** Create API routes file
  - Location: `routes/api/patient.php`
  - Prefix: /api/patient
  - Add middleware group: auth:patient

## Task 3.2: Facility API Endpoints
**Duration: 2 days**
**Dependencies: Phase 2 complete**
**Blocks: Task 5.2 (Facility Dashboard)**

### Day 1: Core Facility Endpoints
- [ ] **3.2.1** Create `FacilityAppointmentController`
  - Location: `app/Http/Controllers/Api/Facility/FacilityAppointmentController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `index()` - GET /api/facility/appointments (filterable list)
    - `show($appointment)` - GET /api/facility/appointments/{id}

- [ ] **3.2.2** Create `AppointmentStatusController`
  - Location: `app/Http/Controllers/Api/Facility/AppointmentStatusController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `update($appointment)` - POST /api/facility/appointments/{id}/status
      - Body: status (enum), notes (optional)

- [ ] **3.2.3** Create `StepStatusController`
  - Location: `app/Http/Controllers/Api/Facility/StepStatusController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `update($step)` - POST /api/facility/appointment-steps/{id}/status
      - Body: status (enum), notes (optional)

- [ ] **3.2.4** Create `DoctorController` (facility view)
  - Location: `app/Http/Controllers/Api/Facility/DoctorController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `index()` - GET /api/facility/doctors
    - `show($doctor)` - GET /api/facility/doctors/{id}

- [ ] **3.2.5** Create `ServiceController` (facility view)
  - Location: `app/Http/Controllers/Api/Facility/ServiceController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `index()` - GET /api/facility/services
    - `store()` - POST /api/facility/services/offerings

### Day 2: Availability Management
- [ ] **3.2.6** Create `AvailabilityController`
  - Location: `app/Http/Controllers/Api/Facility/AvailabilityController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `index()` - GET /api/facility/availability (list rules)

- [ ] **3.2.7** Create `AvailabilityRuleController`
  - Location: `app/Http/Controllers/Api/Facility/AvailabilityRuleController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `store()` - POST /api/facility/availability/rules

- [ ] **3.2.8** Create `AvailabilityExceptionController`
  - Location: `app/Http/Controllers/Api/Facility/AvailabilityExceptionController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `store()` - POST /api/facility/availability/exceptions

- [ ] **3.2.9** Create `SlotGeneratorController`
  - Location: `app/Http/Controllers/Api/Facility/SlotGeneratorController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `generate()` - POST /api/facility/availability/generate-slots
      - Body: start_date, end_date, doctor_id (optional)

- [ ] **3.2.10** Create `WorkflowController`
  - Location: `app/Http/Controllers/Api/Facility/WorkflowController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `store($doctor)` - POST /api/facility/doctors/{id}/workflows

- [ ] **3.2.11** Create `ServiceOfferingController`
  - Location: `app/Http/Controllers/Api/Facility/ServiceOfferingController.php`
  - Middleware: `auth:facility`
  - Methods:
    - `store()` - POST /api/facility/services/offerings

### Day 3: Form Requests & Authorization
- [ ] **3.2.12** Create `UpdateAppointmentStatusRequest`
  - Location: `app/Http/Requests/Facility/UpdateAppointmentStatusRequest.php`
  - Validation: status (valid enum transition), notes (optional)
  - Authorization: facility user can only update their facility's appointments

- [ ] **3.2.13** Create `UpdateStepStatusRequest`
  - Location: `app/Http/Requests/Facility/UpdateStepStatusRequest.php`
  - Validation: status (valid enum), notes (optional)
  - Authorization: facility user can only update their facility's steps

- [ ] **3.2.14** Create `CreateAvailabilityRuleRequest`
  - Location: `app/Http/Requests/Facility/CreateAvailabilityRuleRequest.php`
  - Validation: doctor_id, day_of_week, start_time, end_time, slot_duration_minutes

- [ ] **3.2.15** Create `CreateAvailabilityExceptionRequest`
  - Location: `app/Http/Requests/Facility/CreateAvailabilityExceptionRequest.php`
  - Validation: doctor_id, start_at, end_at, type

- [ ] **3.2.16** Create `GenerateSlotsRequest`
  - Location: `app/Http/Requests/Facility/GenerateSlotsRequest.php`
  - Validation: start_date, end_date, doctor_id (optional)

- [ ] **3.2.17** Create API routes file
  - Location: `routes/api/facility.php`
  - Prefix: /api/facility
  - Add middleware group: auth:facility

## Task 3.3: Slot Generation Logic
**Duration: 1 day**
**Dependencies: Phase 2 complete**
**Blocks: Task 4.1 (Background Job)**
**Can run parallel with: Tasks 3.1, 3.2, 3.4**

- [ ] **3.3.1** Create `SlotGeneratorService`
  - Location: `app/Services/SlotGeneratorService.php`
  - Method: `generateSlots($facilityId, $doctorId, $startDate, $endDate)`
  - Logic:
    - Get all active availability_rules for doctor/facility
    - For each rule, generate slots based on day_of_week
    - Check for exceptions (blocked/emergency) and skip those dates
    - Create availability_slots records
    - Set status='open', capacity=1

- [ ] **3.3.2** Create `SlotGeneratorServiceTest`
  - Unit tests for slot generation logic
  - Test: normal rule generation
  - Test: exception blocking
  - Test: service-specific rules

- [ ] **3.3.3** Integrate service into `SlotGeneratorController`
  - Call service from controller
  - Return count of slots generated

## Task 3.4: Status Update Logic (No-Show Cascade)
**Duration: 1 day**
**Dependencies: Phase 2 complete**
**Can run parallel with: Tasks 3.1, 3.2, 3.3**

- [ ] **3.4.1** Create `AppointmentStatusService`
  - Location: `app/Services/AppointmentStatusService.php`
  - Method: `updateStatus($appointment, $newStatus, $notes)`
  - Logic:
    - Validate status transition (e.g., scheduled → checked_in is valid)
    - Update appointment status
    - If newStatus == 'no_show', call cascade method
    - Log update (optional)

- [ ] **3.4.2** Create `StepStatusService`
  - Location: `app/Services/StepStatusService.php`
  - Method: `updateStatus($step, $newStatus, $notes)`
  - Logic:
    - Validate status transition
    - Update step status
    - If newStatus == 'no_show', cascade to subsequent steps
    - Find all steps with position > this step's position
    - Update all to 'cancelled'

- [ ] **3.4.3** Create `AppointmentObserver`
  - Location: `app/Observers/AppointmentObserver.php`
  - Listen to: updating event
  - If status changed to 'no_show', cascade to steps

- [ ] **3.4.4** Create `AppointmentStepObserver`
  - Location: `app/Observers/AppointmentStepObserver.php`
  - Listen to: updating event
  - If status changed to 'no_show', cascade to higher position steps

- [ ] **3.4.5** Register observers in `AppServiceProvider`
  - Location: `app/Providers/AppServiceProvider.php`
  - In boot() method: Appointment::observe(AppointmentObserver::class)

- [ ] **3.4.6** Create status transition tests
  - Test valid transitions
  - Test invalid transitions (should fail)
  - Test no-show cascade
  - Test step cascade

## API Response Format Standards

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional success message"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... } // validation errors
}
```

### Pagination
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

## Verification Checklist
- [ ] All patient API endpoints return correct data
- [ ] All facility API endpoints return correct data
- [ ] Authentication middleware works on all protected routes
- [ ] Slot generation creates correct number of slots
- [ ] No-show cascade cancels subsequent steps
- [ ] Form request validation works
- [ ] Authorization checks work (patients can only access own data)
- [ ] API returns consistent response format
- [ ] Test with Postman/Insomnia collection
