<!-- 00384c4d-e933-4dca-b881-9f2dfc02ebac 0c8c81cf-709e-4a7b-a41c-a24cebb13e0b -->
# Database Migration Generation Plan

## Overview

Generate all database migrations for the Healthcare Scheduler application following the strict dependency order specified in `01-database-migrations.md`. This is Phase 1 of the development roadmap and must be completed before any other development work.

## Implementation Strategy

### Day 1: Core Reference Tables

Create migrations for tables with no dependencies:

1. **Create facilities table migration**

- Command: `php artisan make:migration create_facilities_table`
- File: `database/migrations/YYYY_MM_DD_000001_create_facilities_table.php`

2. **Create doctors table migration**

- Command: `php artisan make:migration create_doctors_table`
- File: `database/migrations/YYYY_MM_DD_000002_create_doctors_table.php`

3. **Create services table migration**

- Command: `php artisan make:migration create_services_table`
- File: `database/migrations/YYYY_MM_DD_000003_create_services_table.php`

4. **Create step_templates table migration**

- Command: `php artisan make:migration create_step_templates_table`
- File: `database/migrations/YYYY_MM_DD_000004_create_step_templates_table.php`

5. **Create insurance_providers table migration**

- Command: `php artisan make:migration create_insurance_providers_table`
- File: `database/migrations/YYYY_MM_DD_000005_create_insurance_providers_table.php`

6. **Run Day 1 migrations**: `php artisan migrate`

7. **Create factories** for Day 1 tables in `database/factories/`

8. **Create seeders** for Day 1 tables in `database/seeders/`

### Day 2: Dependent Tables

Create migrations for tables that depend on Day 1 tables:

9. **Create facility_doctors pivot table migration**

- Command: `php artisan make:migration create_facility_doctors_table`
- File: `database/migrations/YYYY_MM_DD_000006_create_facility_doctors_table.php`

10. **Create insurance_plans table migration**

- Command: `php artisan make:migration create_insurance_plans_table`
- File: `database/migrations/YYYY_MM_DD_000007_create_insurance_plans_table.php`

11. **Create facility_insurance_plans table migration**

- Command: `php artisan make:migration create_facility_insurance_plans_table`
- File: `database/migrations/YYYY_MM_DD_000008_create_facility_insurance_plans_table.php`

12. **Create service_offerings table migration**

- Command: `php artisan make:migration create_service_offerings_table`
- File: `database/migrations/YYYY_MM_DD_000009_create_service_offerings_table.php`

13. **Create service_workflows table migration**

- Command: `php artisan make:migration create_service_workflows_table`
- File: `database/migrations/YYYY_MM_DD_000010_create_service_workflows_table.php`

14. **Create workflow_steps table migration**

- Command: `php artisan make:migration create_workflow_steps_table`
- File: `database/migrations/YYYY_MM_DD_000011_create_workflow_steps_table.php`

15. **Run Day 2 migrations**: `php artisan migrate`

16. **Create factories and seeders** for Day 2 tables

### Day 3: Scheduling & Booking Tables

Create migrations for the final set of tables:

17. **Create availability_rules table migration**

- Command: `php artisan make:migration create_availability_rules_table`
- File: `database/migrations/YYYY_MM_DD_000012_create_availability_rules_table.php`

18. **Create availability_exceptions table migration**

- Command: `php artisan make:migration create_availability_exceptions_table`
- File: `database/migrations/YYYY_MM_DD_000013_create_availability_exceptions_table.php`

19. **Create availability_slots table migration**

- Command: `php artisan make:migration create_availability_slots_table`
- File: `database/migrations/YYYY_MM_DD_000014_create_availability_slots_table.php`

20. **Create patients table migration**

- Command: `php artisan make:migration create_patients_table`
- File: `database/migrations/YYYY_MM_DD_000015_create_patients_table.php`

21. **Create appointments table migration**

- Command: `php artisan make:migration create_appointments_table`
- File: `database/migrations/YYYY_MM_DD_000016_create_appointments_table.php`

22. **Create appointment_steps table migration**

- Command: `php artisan make:migration create_appointment_steps_table`
- File: `database/migrations/YYYY_MM_DD_000017_create_appointment_steps_table.php`

23. **Run Day 3 migrations**: `php artisan migrate`

24. **Create factories and seeders** for Day 3 tables

### Verification Phase

25. **Run full migration test**: `php artisan migrate:fresh`
26. **Test seeders**: `php artisan db:seed`
27. **Verify foreign key constraints** are correct
28. **Verify indexes** are created properly
29. **Test soft deletes** work on relevant tables

### Progress Tracking

30. **Update 00-priority-roadmap.md**: Mark Phase 1 as completed

## Key Considerations

- Migration files will be created with timestamps to ensure proper execution order
- Each migration will include the exact schema specifications from `01-database-migrations.md`
- Foreign key constraints must match the `onDelete` behavior specified (cascade, restrict, set null)
- All tables requiring soft deletes will include `$table->softDeletes()`
- Indexes will be added as specified for performance optimization
- Factories and seeders will be created to enable testing and development

### To-dos

- [ ] Create migrations for Day 1 core tables (facilities, doctors, services, step_templates, insurance_providers)
- [ ] Run migrations for Day 1 tables: php artisan migrate
- [ ] Create factories and seeders for Day 1 tables
- [ ] Create migrations for Day 2 dependent tables (facility_doctors, insurance_plans, facility_insurance_plans, service_offerings, service_workflows, workflow_steps)
- [ ] Run migrations for Day 2 tables: php artisan migrate
- [ ] Create factories and seeders for Day 2 tables
- [ ] Create migrations for Day 3 scheduling tables (availability_rules, availability_exceptions, availability_slots, patients, appointments, appointment_steps)
- [ ] Run migrations for Day 3 tables: php artisan migrate
- [ ] Create factories and seeders for Day 3 tables
- [ ] Run verification tests: migrate:fresh, db:seed, verify constraints, indexes, and soft deletes
- [ ] Update 00-priority-roadmap.md to mark Phase 1 as completed