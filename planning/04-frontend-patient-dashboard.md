# Phase 5.1: Frontend Patient Dashboard
**Priority: MEDIUM - Depends on Task 3.1**
**Estimated Time: 2-3 days**
**Dependencies: Patient API must be complete**
**Blocks: Nothing (independent from facility dashboard)**

## Day 1: Authentication & Layout

### Login/Registration Pages
- [ ] **5.1.1** Create `PatientLogin.tsx` page component
  - Location: `resources/js/pages/Patient/Login.tsx`
  - Form: email, password
  - Submit to POST /patient/login
  - Store auth token in localStorage
  - Redirect to /patient/dashboard on success
  - Show error messages

- [ ] **5.1.2** Create `PatientRegister.tsx` page component
  - Location: `resources/js/pages/Patient/Register.tsx`
  - Form: first_name, last_name, email, phone, password, password_confirmation, dob
  - Submit to POST /patient/register
  - Auto-login after registration
  - Redirect to /patient/dashboard

- [ ] **5.1.3** Create `PatientDashboardLayout.tsx`
  - Location: `resources/js/layouts/PatientDashboardLayout.tsx`
  - Header with patient name, logout button
  - Sidebar navigation: Dashboard, Appointments, Profile
  - Main content area
  - Mobile responsive

### Core Types & Services
- [ ] **5.1.4** Create patient API service
  - Location: `resources/js/services/patientApi.ts`
  - Functions:
    - login(credentials)
    - register(data)
    - logout()
    - getAppointments()
    - getAppointment(id)
    - bookAppointment(data)
    - cancelAppointment(id)
    - getAvailableSlots(params)
    - getProfile()
    - updateProfile(data)

- [ ] **5.1.5** Create TypeScript interfaces
  - Location: `resources/js/types/patient.ts`
  - Interfaces: Patient, Appointment, AppointmentStep, AvailableSlot, Facility, Service

## Day 2: Dashboard & Appointments

### Dashboard Page
- [ ] **5.1.6** Create `PatientDashboard.tsx` page
  - Location: `resources/js/pages/Patient/Dashboard.tsx`
  - Show upcoming appointments (next 3)
  - Quick action: Book New Appointment button
  - Recent activity (optional for POC)
  - Use useEffect to fetch data on mount

### Appointment List
- [ ] **5.1.7** Create `AppointmentList.tsx` component
  - Location: `resources/js/components/Patient/AppointmentList.tsx`
  - Props: appointments[]
  - Display: date, time, doctor, facility, service, status
  - Status badges with colors
  - Link to detail page
  - Cancel button for scheduled appointments

- [ ] **5.1.8** Create `PatientAppointments.tsx` page
  - Location: `resources/js/pages/Patient/Appointments.tsx`
  - List all appointments (paginated)
  - Filters: upcoming, past, status
  - Use AppointmentList component

### Appointment Detail
- [ ] **5.1.9** Create `AppointmentDetail.tsx` page
  - Location: `resources/js/pages/Patient/AppointmentDetail.tsx`
  - Show appointment details
  - Show workflow steps with status
  - Cancel appointment button (if scheduled)
  - Use params.id to fetch appointment

## Day 3: Booking Flow & Profile

### Booking Flow
- [ ] **5.1.10** Create `BookingFlow.tsx` component
  - Location: `resources/js/components/Patient/BookingFlow.tsx`
  - Multi-step wizard:
    - Step 1: Select Facility (from API)
    - Step 2: Select Service (filtered by facility)
    - Step 3: Select Doctor (filtered by service+facility)
    - Step 4: Select Date & Time Slot (calendar view)
    - Step 5: Confirm & Book

- [ ] **5.1.11** Create `FacilitySelector.tsx`
  - Location: `resources/js/components/Patient/FacilitySelector.tsx`
  - Props: facilities[], onSelect
  - Display cards with facility info

- [ ] **5.1.12** Create `ServiceSelector.tsx`
  - Location: `resources/js/components/Patient/ServiceSelector.tsx`
  - Props: services[], onSelect
  - Display service cards with duration

- [ ] **5.1.13** Create `DoctorSelector.tsx`
  - Location: `resources/js/components/Patient/DoctorSelector.tsx`
  - Props: doctors[], onSelect
  - Display doctor cards with specialty

- [ ] **5.1.14** Create `SlotCalendar.tsx`
  - Location: `resources/js/components/Patient/SlotCalendar.tsx`
  - Props: slots[], onSelect
  - Show calendar grid
  - Available slots per day
  - Time slot buttons

- [ ] **5.1.15** Create `BookingConfirmation.tsx`
  - Location: `resources/js/components/Patient/BookingConfirmation.tsx`
  - Props: selectedFacility, selectedService, selectedDoctor, selectedSlot
  - Show summary
  - Confirm button
  - Submit to API

### Profile Management
- [ ] **5.1.16** Create `PatientProfile.tsx` page
  - Location: `resources/js/pages/Patient/Profile.tsx`
  - Form: first_name, last_name, email, phone, dob, preferred_language
  - Submit to PUT /api/patient/profile
  - Password change (optional for POC)

## Routes Configuration
- [ ] **5.1.17** Create patient routes file
  - Location: `resources/js/routes/patient.tsx`
  - Routes:
    - /patient/login → PatientLogin
    - /patient/register → PatientRegister
    - /patient/dashboard → PatientDashboard (protected)
    - /patient/appointments → PatientAppointments (protected)
    - /patient/appointments/:id → AppointmentDetail (protected)
    - /patient/book → BookingFlow (protected)
    - /patient/profile → PatientProfile (protected)

- [ ] **5.1.18** Add patient routes to main router
  - Location: `resources/js/app.tsx`
  - Import and include patient routes

## State Management
- [ ] **5.1.19** Create patient auth context
  - Location: `resources/js/contexts/PatientAuthContext.tsx`
  - Store: patient, isAuthenticated, login, logout
  - Persist in localStorage
  - Provide to app

## UI Components
- [ ] **5.1.20** Create reusable UI components
  - `Button.tsx` - Primary/secondary buttons
  - `Input.tsx` - Form inputs with labels
  - `Select.tsx` - Dropdown select
  - `Card.tsx` - Content cards
  - `Badge.tsx` - Status badges
  - `LoadingSpinner.tsx` - Loading state
  - `ErrorMessage.tsx` - Error display

## Verification Checklist
- [ ] Patient can register
- [ ] Patient can login
- [ ] Patient can view appointments
- [ ] Patient can book appointment (full flow)
- [ ] Patient can cancel appointment
- [ ] Patient can update profile
- [ ] All pages are responsive
- [ ] Loading states work
- [ ] Error messages display correctly
- [ ] Auth protection works (can't access dashboard when logged out)
