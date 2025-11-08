# Healthcare Scheduler - Development Priority Roadmap

## Phase 1: Foundation (Sequential - Must Complete First)
**Duration: 2-3 days**
- **Task 1.1**: Database migrations (ALL tables)
  - Dependencies: None (starts first)
  - Blocks: Everything else

## Phase 2: Core Backend (Sequential - Must Complete Second)
**Duration: 2 days**
- **Task 2.1**: Authentication system (Patient + Facility guards)
  - Dependencies: Phase 1 complete (users tables exist)
  - Blocks: Phase 3

## Phase 3: Backend API (Parallel Execution Possible)
**Duration: 3-4 days**
- **Task 3.1**: Patient API endpoints
  - Dependencies: Phase 2 complete
  - Can run parallel with: Task 3.2, 3.3, 3.4
  - **Note**: Frontend built by coworker, but API still needed
  
- **Task 3.2**: Facility API endpoints
  - Dependencies: Phase 2 complete
  - Can run parallel with: Task 3.1, 3.3, 3.4
  
- **Task 3.3**: Slot generation logic
  - Dependencies: Phase 2 complete
  - Can run parallel with: Task 3.1, 3.2, 3.4
  
- **Task 3.4**: Status update logic (no-show cascade)
  - Dependencies: Phase 2 complete
  - Can run parallel with: Task 3.1, 3.2, 3.3

## Phase 4: Background Jobs (Parallel with Phase 3)
**Duration: 1 day**
- **Task 4.1**: Slot generation job
  - Dependencies: Phase 1 complete
  - Can run parallel with: Phase 3 (all tasks)
  
- **Task 4.2**: Reservation cleanup job
  - Dependencies: Phase 1 complete
  - Can run parallel with: Phase 3 (all tasks)

## Phase 5: Frontend Development (Facility Dashboard Only)
**Duration: 2-3 days** (reduced from 4-5)
- **Task 5.1**: Facility dashboard
  - Dependencies: Task 3.2 complete (Facility API ready)
  - Blocks: Nothing
  - **Note**: Patient dashboard built by coworker - NOT NEEDED

## Phase 6: Testing (Final Phase)
**Duration: 2 days**
- **Task 6.1**: Feature tests
  - Dependencies: All Phase 3, 4, 5 complete
  - **Note**: Test patient API but not patient frontend
  
- **Task 6.2**: Unit tests
  - Dependencies: All Phase 3, 4, 5 complete

## Parallel Execution Summary (Updated)

**With 3-4 agents:**
- **Agent 1**: Phase 1 → Phase 2 → Task 3.1 (Patient API)
- **Agent 2**: (waits for Phase 2) → Task 3.2 (Facility API) → Task 5.1 (Facility Frontend)
- **Agent 3**: (waits for Phase 2) → Task 3.3 (Slot Generation) + Task 3.4 (Status Logic)
- **Agent 4**: (waits for Phase 1) → Phase 4 (Background Jobs)

**With 2 agents:**
- **Agent 1**: Phase 1 → Phase 2 → Phase 3 (all backend APIs)
- **Agent 2**: (waits for Phase 3) → Phase 4 (jobs) + Phase 5 (facility frontend) + Phase 6 (tests)

## Critical Path (Updated)
Phase 1 → Phase 2 → Phase 3 → Phase 5 → Phase 6 = 9-11 days

**Time saved**: 3-4 days by not building patient frontend

## What We're NOT Building
❌ Patient dashboard frontend (coworker handling)
❌ Patient UI components
❌ Patient booking flow UI
❌ Patient profile UI

## What We ARE Building
✅ All database migrations
✅ Authentication system (both guards)
✅ Patient API endpoints (for coworker's frontend)
✅ Facility API endpoints
✅ Facility dashboard frontend
✅ Background jobs
✅ Testing (backend + facility frontend)
