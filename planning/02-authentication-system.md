# Phase 2: Authentication System
**Priority: HIGH - Depends on Phase 1, Blocks Phase 3**
**Estimated Time: 2 days**
**Dependencies: Database migrations must be complete**
**Note: This is BACKEND authentication system. Coworker handles frontend login UI.**

## Day 1: Authentication Backend

### Models & Guards Setup
- [ ] **2.1.1** Create `Patient` model (authenticatable)
  - Location: `app/Models/Patient.php`
  - Extend `Authenticatable` class
  - Add `HasApiTokens` trait (for API authentication)
  - Add fillable: first_name, last_name, email, phone, dob, preferred_language, password
  - Add hidden: password, remember_token
  - Add casts: meta (array), dob (date)
  - **Note**: Frontend login form built by coworker, but this model handles authentication

- [ ] **2.1.2** Create `FacilityUser` model (authenticatable)
  - Location: `app/Models/FacilityUser.php`
  - Extend `Authenticatable` class
  - Add `HasApiTokens` trait
  - Add fillable: name, email, facility_id, role, password
  - Add hidden: password, remember_token
  - Add relationship: belongsTo(Facility::class)
  - **Note**: Frontend login form built by us in facility dashboard

- [ ] **2.1.3** Update `config/auth.php`
  - Add 'patient' guard with 'patients' provider
  - Add 'facility' guard with 'facility_users' provider
  - Configure providers for both models
  - This enables `auth:patient` and `auth:facility` middleware

### Database Updates
- [ ] **2.1.4** Create `users` table migration (if not exists)
  - For facility staff authentication
  - Fields: id, name, email, password, facility_id, role, remember_token
  - Timestamps, soft deletes

- [ ] **2.1.5** Update `patients` table migration
  - Add: password (hashed), remember_token
  - Add: email_verified_at timestamp (optional)
  - These fields required for Laravel authentication

- [ ] **2.1.6** Run migrations: `php artisan migrate`

### Controllers (Backend API)
- [ ] **2.1.7** Create `PatientAuthController`
  - Location: `app/Http/Controllers/Auth/PatientAuthController.php`
  - Methods: login(), logout(), register()
  - Use `auth:patient` guard
  - Return JSON responses ( coworker's frontend will consume these )
  - login() returns token/patient data on success
  - register() creates patient and logs them in

- [ ] **2.1.8** Create `FacilityAuthController`
  - Location: `app/Http/Controllers/Auth/FacilityAuthController.php`
  - Methods: login(), logout()
  - Use `auth:facility` guard
  - Return JSON responses
  - Used by our facility dashboard frontend

### Routes (Backend API Endpoints)
- [ ] **2.1.9** Create auth routes file
  - Location: `routes/auth.php`
  - Patient routes: 
    - POST /api/patient/login
    - POST /api/patient/logout  
    - POST /api/patient/register
  - Facility routes:
    - POST /api/facility/login
    - POST /api/facility/logout
  - No middleware needed (public routes)
  - **Note**: These are API endpoints, not web routes

- [ ] **2.1.10** Register auth routes in `RouteServiceProvider`
  - Add to boot() method
  - Prefix with 'api'

### Middleware
- [ ] **2.1.11** Create `RedirectIfPatientAuthenticated` middleware
  - For web routes (if needed)
  - Redirect to patient dashboard if already logged in
  - May not be needed since we're API-first

- [ ] **2.1.12** Create `RedirectIfFacilityAuthenticated` middleware
  - For facility web routes
  - Redirect to facility dashboard if already logged in

## Day 2: Registration & API Response Format

### Patient Registration API
- [ ] **2.2.1** Create `PatientRegisterRequest` form request
  - Location: `app/Http/Requests/Patient/RegisterRequest.php`
  - Validation: first_name, last_name, email (unique), phone, password (confirmed), dob
  - Rules: email format, password min 8, phone format
  - Authorization: public (anyone can register)

- [ ] **2.2.2** Implement patient registration logic in controller
  - Hash password: `Hash::make($request->password)`
  - Create patient record
  - Auto-login after registration (create session/token)
  - Return JSON: { success: true, patient: {...}, token: '...' }
  - **Coworker's frontend will handle the redirect after receiving this response**

### API Response Format Standards
All auth endpoints should return consistent JSON:

**Success Response (200/201):**
```json
{
  "success": true,
  "data": {
    "patient": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com"
    },
    "token": "api-token-here"
  },
  "message": "Registration successful"
}
```

**Error Response (401/422/500):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Login API Implementation
- [ ] **2.2.3** Implement patient login logic
  - Use `Auth::guard('patient')->attempt($credentials)`
  - Return patient data + token on success
  - Return 401 on failure
  - **Coworker's frontend will show error message if login fails**

- [ ] **2.2.4** Implement facility login logic
  - Similar to patient login but using 'facility' guard
  - Return facility user data + token
  - Used by our facility dashboard

### Logout API Implementation
- [ ] **2.2.5** Implement logout for both guards
  - Invalidate token/session
  - Return success response
  - **Frontend will handle clearing localStorage and redirecting**

## Frontend Integration Points

### For Coworker's Patient Frontend:
- **POST /api/patient/register** - Create new patient account
- **POST /api/patient/login** - Login existing patient
- **POST /api/patient/logout** - Logout patient
- All return JSON responses as specified above
- Frontend should store token in localStorage
- Frontend should include token in Authorization header for subsequent requests

### For Our Facility Frontend:
- **POST /api/facility/login** - Login facility staff
- **POST /api/facility/logout** - Logout facility staff
- Same response format as patient auth

## Testing Auth API
- [ ] **2.2.6** Test patient registration API
  - POST /api/patient/register with valid data
  - Verify 201 status
  - Verify patient created in database
  - Verify password hashed
  - Verify response format matches spec

- [ ] **2.2.7** Test patient login API
  - POST /api/patient/login with valid credentials
  - Verify 200 status
  - Verify patient data in response
  - Verify token returned
  - Test with invalid credentials: verify 401

- [ ] **2.2.8** Test facility login API
  - Create test facility user
  - POST /api/facility/login
  - Verify 200 status
  - Verify user data and token

- [ ] **2.2.9** Test logout API for both guards
  - Verify session/token invalidated
  - Verify 200 success response

- [ ] **2.2.10** Test auth middleware
  - Access protected endpoint without token: expect 401
  - Access with valid token: expect 200
  - Access with invalid token: expect 401

## Frontend Prep (For Our Facility Dashboard)
- [ ] **2.2.11** Create auth types for TypeScript
  - Location: `resources/js/types/auth.ts`
  - Interfaces: FacilityUser, LoginCredentials, AuthResponse
  - **Note**: Coworker creates their own types for patient frontend**

## Verification Checklist
- [ ] Both guards (patient, facility) work independently
- [ ] Patient registration API works
- [ ] Patient login API works
- [ ] Facility login API works
- [ ] Logout API works for both
- [ ] Auth middleware protects routes
- [ ] API returns consistent JSON format
- [ ] Passwords are properly hashed
- [ ] Tokens/sessions work correctly
- [ ] **Coworker can integrate with patient auth API**
- [ ] **We can integrate with facility auth API**
