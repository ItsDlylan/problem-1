# Phase 5.2: Frontend Facility Dashboard
**Priority: MEDIUM - Depends on Task 3.2**
**Estimated Time: 2-3 days**
**Dependencies: Facility API must be complete**
**Blocks: Nothing (independent from patient dashboard)**

## Day 1: Authentication & Layout

### Login Page
- [ ] **5.2.1** Create `FacilityLogin.tsx` page component
  - Location: `resources/js/pages/Facility/Login.tsx`
  - Form: email, password
  - Submit to POST /facility/login
  - Store auth token in localStorage
  - Redirect to /facility/dashboard on success
  - Show error messages

- [ ] **5.2.2** Create `FacilityDashboardLayout.tsx`
  - Location: `resources/js/layouts/FacilityDashboardLayout.tsx`
  - Header with facility name, user name, logout button
  - Sidebar navigation: Dashboard, Appointments, Doctors, Availability, Services
  - Main content area
  - Mobile responsive
  - Role-based menu items (optional for POC)

### Core Types & Services
- [ ] **5.2.3** Create facility API service
  - Location: `resources/js/services/facilityApi.ts`
  - Functions:
    - login(credentials)
    - logout()
    - getAppointments(filters)
    - getAppointment(id)
    - updateAppointmentStatus(id, status, notes)
    - updateStepStatus(id, status, notes)
    - getDoctors()
    - getDoctor(id)
    - createWorkflow(doctorId, data)
    - getAvailability()
    - createAvailabilityRule(data)
    - createAvailabilityException(data)
    - generateSlots(data)
    - getServices()
    - createServiceOffering(data)

- [ ] **5.2.4** Create TypeScript interfaces
  - Location: `resources/js/types/facility.ts`
  - Interfaces: FacilityUser, Doctor, ServiceOffering, AvailabilityRule, AvailabilityException, SlotGenerationParams

## Day 2: Dashboard & Appointment Management

### Dashboard Page
- [ ] **5.2.5** Create `FacilityDashboard.tsx` page
  - Location: `resources/js/pages/Facility/Dashboard.tsx`
  - Show today's appointments
  - Quick stats: total appointments, no-shows, completed
  - Quick actions: Block Time, Generate Slots
  - Use useEffect to fetch data on mount

### Appointment List
- [ ] **5.2.6** Create `AppointmentList.tsx` component
  - Location: `resources/js/components/Facility/AppointmentList.tsx`
  - Props: appointments[], onStatusUpdate
  - Display: time, patient, doctor, service, status
  - Status badges with colors
  - Status update dropdown/buttons
  - Link to detail page
  - Filters: date, doctor, status

- [ ] **5.2.7** Create `FacilityAppointments.tsx` page
  - Location: `resources/js/pages/Facility/Appointments.tsx`
  - List all appointments (paginated)
  - Filters: date range, doctor, status
  - Use AppointmentList component
  - Export button (optional for POC)

### Appointment Detail
- [ ] **5.2.8** Create `AppointmentDetail.tsx` page
  - Location: `resources/js/pages/Facility/AppointmentDetail.tsx`
  - Show appointment details
  - Show workflow steps with status
  - Update status buttons for each step
  - Update overall appointment status
  - Use params.id to fetch appointment

### Status Updaters
- [ ] **5.2.9** Create `AppointmentStatusUpdater.tsx` component
  - Location: `resources/js/components/Facility/AppointmentStatusUpdater.tsx`
  - Props: appointment, onUpdate
  - Buttons for each valid status transition
  - Confirm dialog for no-show/cancel
  - Call API on click

- [ ] **5.2.10** Create `StepStatusUpdater.tsx` component
  - Location: `resources/js/components/Facility/StepStatusUpdater.tsx`
  - Props: step, onUpdate
  - Buttons: Start, Complete, No Show
  - Show only for current step or in-progress steps
  - Call API on click

## Day 3: Availability & Doctor Management

### Availability Calendar
- [ ] **5.2.11** Create `AvailabilityCalendar.tsx` component
  - Location: `resources/js/components/Facility/AvailabilityCalendar.tsx`
  - Show calendar grid (month/week view)
  - Show availability rules as colored blocks
  - Show exceptions (blocked times)
  - Click date to add exception
  - Doctor filter dropdown

- [ ] **5.2.12** Create `AvailabilityRuleForm.tsx` component
  - Location: `resources/js/components/Facility/AvailabilityRuleForm.tsx`
  - Form: doctor_id, day_of_week, start_time, end_time, slot_duration_minutes
  - Submit to create availability rule
  - Modal or separate page

- [ ] **5.2.13** Create `ExceptionForm.tsx` component
  - Location: `resources/js/components/Facility/ExceptionForm.tsx`
  - Form: doctor_id, start_at, end_at, type (blocked/override/emergency)
  - Submit to create exception
  - Pre-fill date if clicked from calendar
  - Modal form

- [ ] **5.2.14** Create `SlotGenerator.tsx` component
  - Location: `resources/js/components/Facility/SlotGenerator.tsx`
  - Form: start_date, end_date, doctor_id (optional)
  - Submit to generate slots
  - Show progress/result
  - Button on availability page

### Doctor Management
- [ ] **5.2.15** Create `DoctorList.tsx` page
  - Location: `resources/js/pages/Facility/Doctors.tsx`
  - List all doctors for facility
  - Show: name, specialty, active status
  - Link to doctor detail
  - Add doctor button (optional for POC)

- [ ] **5.2.16** Create `DoctorDetail.tsx` page
  - Location: `resources/js/pages/Facility/DoctorDetail.tsx`
  - Show doctor details
  - Show assigned services
  - Show availability rules
  - Edit button (optional for POC)

### Service & Workflow Management
- [ ] **5.2.17** Create `ServiceOfferingForm.tsx` component
  - Location: `resources/js/components/Facility/ServiceOfferingForm.tsx`
  - Form: service_id, doctor_id, facility_id, default_duration_minutes, visibility
  - Submit to assign service to doctor
  - Modal or separate page

- [ ] **5.2.18** Create `WorkflowBuilder.tsx` component
  - Location: `resources/js/components/Facility/WorkflowBuilder.tsx`
  - Show list of step templates
  - Drag-and-drop to order steps (or simple up/down buttons)
  - Set duration for each step
  - Save workflow
  - Props: doctor_id, service_offering_id

## Routes Configuration
- [ ] **5.2.19** Create facility routes file
  - Location: `resources/js/routes/facility.tsx`
  - Routes:
    - /facility/login → FacilityLogin
    - /facility/dashboard → FacilityDashboard (protected)
    - /facility/appointments → FacilityAppointments (protected)
    - /facility/appointments/:id → AppointmentDetail (protected)
    - /facility/doctors → DoctorList (protected)
    - /facility/doctors/:id → DoctorDetail (protected)
    - /facility/availability → AvailabilityCalendar (protected)
    - /facility/services → ServiceList (protected)

- [ ] **5.2.20** Add facility routes to main router
  - Location: `resources/js/app.tsx`
  - Import and include facility routes

## State Management
- [ ] **5.2.21** Create facility auth context
  - Location: `resources/js/contexts/FacilityAuthContext.tsx`
  - Store: user, facility, isAuthenticated, login, logout
  - Persist in localStorage
  - Provide to app

## UI Components
- [ ] **5.2.22** Create reusable UI components (if not created in patient dashboard)
  - `Button.tsx` - Primary/secondary/danger buttons
  - `Input.tsx` - Form inputs
  - `Select.tsx` - Dropdown select
  - `Card.tsx` - Content cards
  - `Badge.tsx` - Status badges
  - `LoadingSpinner.tsx` - Loading state
  - `ErrorMessage.tsx` - Error display
  - `Modal.tsx` - Modal dialog
  - `Calendar.tsx` - Calendar grid
  - `DataTable.tsx` - Sortable/filterable table

## Verification Checklist
- [ ] Facility user can login
- [ ] Facility user can view all appointments
- [ ] Facility user can update appointment status
- [ ] Facility user can update step status
- [ ] No-show cascade works in UI
- [ ] Facility user can create availability rules
- [ ] Facility user can create exceptions
- [ ] Facility user can generate slots
- [ ] Facility user can assign services to doctors
- [ ] All pages are responsive
- [ ] Loading states work
- [ ] Error messages display correctly
- [ ] Auth protection works
