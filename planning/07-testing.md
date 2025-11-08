# Phase 6: Testing
**Priority: LOWEST - Depends on all other phases**
**Estimated Time: 1.5 days** (reduced from 2 days)
**Dependencies: All Phase 3, 4, 5 tasks must be complete**
**Final Phase - No blocks**
**Note: Coworker tests patient frontend - we test backend APIs and facility frontend**

## Task 6.1: Feature Tests (Browser/Integration Tests)
**Duration: 0.5 day** (reduced from 1 day)
**Dependencies: All backend and frontend features complete**
**Note: Only test facility frontend, not patient frontend**

### Patient API Tests (Backend Only)
- [ ] **6.1.1** Test patient registration API
  - POST /api/patient/register with valid data
  - Verify 201 status
  - Verify patient created in database
  - Verify password hashed
  - Verify response format matches spec

- [ ] **6.1.2** Test patient login API
  - Create patient in database
  - POST /api/patient/login with valid credentials
  - Verify 200 status
  - Verify patient data in response
  - Verify token returned
  - Test with invalid credentials: verify 401

- [ ] **6.1.3** Test patient appointment booking API
  - Create patient and login
  - Create facility, doctor, service, slot
  - POST /api/patient/appointments with valid data
  - Verify 201 status
  - Verify appointment created
  - Verify slot marked as booked
  - Verify appointment_steps created

- [ ] **6.1.4** Test patient appointment cancellation API
  - Create patient and appointment
  - POST /api/patient/appointments/{id}/cancel
  - Verify 200 status
  - Verify appointment status = 'cancelled'
  - Verify slot status = 'open'

- [ ] **6.1.5** Test patient profile API
  - Create patient and login
  - GET /api/patient/profile
  - Verify 200 status
  - Verify correct patient data
  - PUT /api/patient/profile with updates
  - Verify database updated

### Facility Feature Tests (Frontend + Backend)
- [ ] **6.1.6** Test facility login flow
  - Create facility user in database
  - Visit /facility/login
  - Fill form with credentials
  - Submit
  - Assert redirected to dashboard
  - Assert facility user logged in

- [ ] **6.1.7** Test facility appointment status update
  - Login as facility user
  - Create appointment
  - Visit /facility/appointments
  - Click status update button
  - Select new status
  - Assert appointment status updated

- [ ] **6.1.8** Test facility step status update (no-show cascade)
  - Login as facility user
  - Create appointment with workflow steps
  - Visit appointment detail
  - Mark step 1 as 'no_show'
  - Assert step 1 status = 'no_show'
  - Assert steps 2, 3, etc. status = 'cancelled'

- [ ] **6.1.9** Test availability rule creation
  - Login as facility user
  - Visit /facility/availability
  - Click add rule
  - Fill form: doctor, day, times
  - Submit
  - Assert rule created in database

- [ ] **6.1.10** Test availability exception creation
  - Login as facility user
  - Visit /facility/availability
  - Click date on calendar
  - Fill exception form
  - Submit
  - Assert exception created
  - Assert slots removed for that time

- [ ] **6.1.11** Test slot generation
  - Login as facility user
  - Create availability rule
  - Visit /facility/availability
  - Click generate slots
  - Select date range
  - Submit
  - Assert slots created in database
  - Assert correct number of slots

## Task 6.2: Unit Tests
**Duration: 1 day**
**Dependencies: All backend services complete**

### Service Tests
- [ ] **6.2.1** Test `SlotGeneratorService`
  - Test generates correct number of slots
  - Test respects slot duration
  - Test respects exceptions
  - Test service-specific rules
  - Test batch insert performance

- [ ] **6.2.2** Test `AppointmentStatusService`
  - Test valid status transitions
  - Test invalid status transitions (expect exception)
  - Test no-show triggers cascade
  - Test notes are saved

- [ ] **6.2.3** Test `StepStatusService`
  - Test valid status transitions
  - Test no-show cascade to subsequent steps
  - Test position-based cascade
  - Test notes are saved

### Model Tests
- [ ] **6.2.4** Test `Appointment` model relationships
  - Test belongs to patient, doctor, facility, service_offering
  - Test has many appointment_steps (ordered)
  - Test belongs to slot

- [ ] **6.2.5** Test `AvailabilitySlot` model scopes
  - Test scopeOpen() returns only open slots
  - Test scopeReserved() returns only reserved slots
  - Test scopeBooked() returns only booked slots

- [ ] **6.2.6** Test `AvailabilityRule` model methods
  - Test getSlotsForDate() returns correct slots
  - Test isAvailableOnDate() checks exceptions

### Observer Tests
- [ ] **6.2.7** Test `AppointmentObserver` no-show cascade
  - Create appointment with steps
  - Update status to 'no_show'
  - Assert all steps cancelled

- [ ] **6.2.8** Test `AppointmentStepObserver` cascade
  - Create appointment with multiple steps
  - Update step 1 to 'no_show'
  - Assert steps 2+ cancelled
  - Assert step 1 remains 'no_show'

## Task 6.3: API Endpoint Tests
**Duration: 0.5 day**
**Dependencies: All API endpoints complete**

### Patient API Tests (Backend Only)
- [ ] **6.3.1** Test GET /api/patient/appointments
  - Without auth: expect 401
  - With auth: expect list of patient's appointments
  - Test pagination

- [ ] **6.3.2** Test POST /api/patient/appointments
  - Without auth: expect 401
  - With auth and valid data: expect 201 + appointment
  - With invalid slot: expect 422
  - Test slot reservation → booking flow

- [ ] **6.3.3** Test POST /api/patient/appointments/{id}/cancel
  - Without auth: expect 401
  - Cancel own appointment: expect 200
  - Cancel other's appointment: expect 403
  - Test slot status change

### Facility API Tests
- [ ] **6.3.4** Test POST /api/facility/appointments/{id}/status
  - Without auth: expect 401
  - With facility auth: expect 200
  - Test invalid status: expect 422
  - Test no-show cascade

- [ ] **6.3.5** Test POST /api/facility/appointment-steps/{id}/status
  - Without auth: expect 401
  - Update step status: expect 200
  - Test no-show cascade to subsequent steps

- [ ] **6.3.6** Test POST /api/facility/availability/generate-slots
  - Without auth: expect 401
  - Generate slots: expect 200 + count
  - Verify slots created in database

## Task 6.4: Background Job Tests
**Duration: 0.5 day**
**Dependencies: Phase 4 complete**

- [ ] **6.4.1** Test slot generation job
  - Create availability rule
  - Dispatch job
  - Assert slots created
  - Assert correct count

- [ ] **6.4.2** Test reservation cleanup job
  - Create reserved slot with expired time
  - Dispatch job
  - Assert slot status = 'open'
  - Assert reserved_until = NULL

- [ ] **6.4.3** Test scheduler
  - Run `php artisan schedule:run`
  - Assert jobs dispatched
  - Run queue worker
  - Assert jobs executed

## Test Data Setup
- [ ] **6.5.1** Create test factories
  - Factory for each model
  - Realistic fake data
  - Use Faker for names, addresses, etc.

- [ ] **6.5.2** Create test seeders
  - Seed test data for local development
  - Create sample facility, doctors, services
  - Create sample availability rules
  - Create sample appointments

- [ ] **6.5.3** Create testing utilities
  - Helper functions for common test actions
  - Create patient and login
  - Create facility user and login
  - Create appointment with workflow

## Performance Tests (Optional for POC)
- [ ] **6.6.1** Test slot generation performance
  - Generate slots for 30 days
  - Measure execution time
  - Assert completes in < 5 seconds

- [ ] **6.6.2** Test API response times
  - Test appointment list endpoint
  - Assert response < 200ms
  - Test with 1000+ appointments

## Security Tests
- [ ] **6.7.1** Test authorization
  - Patient cannot access facility endpoints
  - Facility user cannot access patient endpoints
  - Patient can only see own appointments
  - Facility user can only see own facility data

- [ ] **6.7.2** Test authentication
  - Access protected route without token: expect 401
  - Access with invalid token: expect 401
  - Access with expired token: expect 401

## Verification Checklist
- [ ] All feature tests pass
- [ ] All unit tests pass
- [ ] All API tests pass
- [ ] All background job tests pass
- [ ] Test coverage > 80% (optional for POC)
- [ ] **Patient API tests pass (for coworker's integration)**
- [ ] Facility frontend tests pass
- [ ] All edge cases tested
- [ ] Authorization tests pass
- [ ] Performance acceptable
- [ ] Test suite runs in CI/CD (optional)
