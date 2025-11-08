# Phase 2: Authentication System
**Priority: HIGH - Depends on Phase 1, Blocks Phase 3**
**Estimated Time: 2 days**
**Dependencies: Database migrations must be complete**

## Day 1: Patient Authentication

### Models & Guards Setup
- [ ] **2.1.1** Create `Patient` model (authenticatable)
  - Location: `app/Models/Patient.php`
  - Extend `Authenticatable` class
  - Add `HasApiTokens` trait
  - Add fillable: first_name, last_name, email, phone, dob, preferred_language
  - Add hidden: password, remember_token
  - Add casts: meta (array), dob (date)

- [ ] **2.1.2** Create `FacilityUser` model (authenticatable)
  - Location: `app/Models/FacilityUser.php`
  - Extend `Authenticatable` class
  - Add `HasApiTokens` trait
  - Add fillable: name, email, facility_id, role
  - Add hidden: password, remember_token
  - Add relationship: belongsTo(Facility::class)

- [ ] **2.1.3** Update `config/auth.php`
  - Add 'patient' guard with 'patients' provider
  - Add 'facility' guard with 'facility_users' provider
  - Configure providers for both models

### Database Updates
- [ ] **2.1.4** Create `users` table migration (if not exists)
  - For facility staff authentication
  - Fields: id, name, email, password, facility_id, role, remember_token
  - Timestamps, soft deletes

- [ ] **2.1.5** Update `patients` table migration
  - Add: password (hashed), remember_token
  - Add: email_verified_at timestamp

- [ ] **2.1.6** Run migrations: `php artisan migrate`

### Controllers
- [ ] **2.1.7** Create `PatientAuthController`
  - Location: `app/Http/Controllers/Auth/PatientAuthController.php`
  - Methods: login(), logout(), register()
  - Use `auth:patient` guard
  - Return JSON responses

- [ ] **2.1.8** Create `FacilityAuthController`
  - Location: `app/Http/Controllers/Auth/FacilityAuthController.php`
  - Methods: login(), logout()
  - Use `auth:facility` guard
  - Return JSON responses

### Routes
- [ ] **2.1.9** Create auth routes file
  - Location: `routes/auth.php`
  - Patient routes: POST /patient/login, POST /patient/logout, POST /patient/register
  - Facility routes: POST /facility/login, POST /facility/logout
  - No middleware needed (public routes)

- [ ] **2.1.10** Register auth routes in `RouteServiceProvider`

### Middleware
- [ ] **2.1.11** Create `RedirectIfPatientAuthenticated` middleware
  - Redirect to patient dashboard if already logged in

- [ ] **2.1.12** Create `RedirectIfFacilityAuthenticated` middleware
  - Redirect to facility dashboard if already logged in

## Day 2: Registration & Verification

### Patient Registration
- [ ] **2.2.1** Create `PatientRegisterRequest` form request
  - Validation: first_name, last_name, email (unique), phone, password (confirmed), dob
  - Rules: email format, password min 8, phone format

- [ ] **2.2.2** Implement patient registration logic
  - Hash password
  - Create patient record
  - Auto-login after registration
  - Return patient data + token

### Email Verification (Optional for POC)
- [ ] **2.2.3** Add `MustVerifyEmail` contract to Patient model
- [ ] **2.2.4** Create email verification routes (if needed)
- [ ] **2.2.5** Create verification notification

### Password Reset (Optional for POC)
- [ ] **2.2.6** Create password reset routes (if needed)
- [ ] **2.2.7** Create reset notification

### Testing Auth
- [ ] **2.2.8** Test patient registration
  - POST /patient/register with valid data
  - Verify patient created in database
  - Verify can login with new credentials

- [ ] **2.2.9** Test patient login
  - POST /patient/login with valid credentials
  - Verify authentication works
  - Verify token returned

- [ ] **2.2.10** Test facility login
  - Create test facility user
  - POST /facility/login
  - Verify authentication works

- [ ] **2.2.11** Test logout for both guards
  - Verify session cleared
  - Verify token invalidated (if using API tokens)

### Frontend Prep
- [ ] **2.2.12** Create auth types for TypeScript
  - Location: `resources/js/types/auth.ts`
  - Interfaces: Patient, FacilityUser, LoginCredentials, RegisterCredentials

## Verification Checklist
- [ ] Both guards work independently
- [ ] Patient can register and login
- [ ] Facility user can login
- [ ] Middleware redirects work correctly
- [ ] Auth routes return proper JSON responses
- [ ] Passwords are properly hashed
- [ ] Remember me functionality works (optional)
