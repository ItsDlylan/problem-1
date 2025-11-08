# Phase 1: Database Migrations
**Priority: HIGHEST - Must be completed first**
**Estimated Time: 2-3 days**

## Migration Creation Order (Critical - must be in this order due to FK dependencies)

### Day 1: Core Reference Tables (no dependencies)
- [ ] **1.1** Create `facilities` table migration
- [ ] **1.2** Create `doctors` table migration
- [ ] **1.3** Create `services` table migration
- [ ] **1.4** Create `step_templates` table migration
- [ ] **1.5** Create `insurance_providers` table migration
- [ ] **1.6** Run migrations: `php artisan migrate`
- [ ] **1.7** Create factories for above tables
- [ ] **1.8** Create seeders for above tables

### Day 2: Dependent Tables (relate to core tables)
- [ ] **2.1** Create `facility_doctors` pivot table migration
- [ ] **2.2** Create `insurance_plans` table migration (depends on insurance_providers)
- [ ] **2.3** Create `facility_insurance_plans` table migration (depends on facilities, insurance_plans)
- [ ] **2.4** Create `service_offerings` table migration (depends on services, doctors, facilities)
- [ ] **2.5** Create `service_workflows` table migration (depends on service_offerings, insurance_plans)
- [ ] **2.6** Create `workflow_steps` table migration (depends on service_workflows, step_templates)
- [ ] **2.7** Run migrations: `php artisan migrate`
- [ ] **2.8** Create factories and seeders

### Day 3: Scheduling & Booking Tables
- [ ] **3.1** Create `availability_rules` table migration (depends on doctors, facilities, service_offerings)
- [ ] **3.2** Create `availability_exceptions` table migration (depends on availability_rules, facilities, doctors)
- [ ] **3.3** Create `availability_slots` table migration (depends on facilities, doctors, service_offerings, availability_rules)
- [ ] **3.4** Create `patients` table migration (depends on insurance_plans)
- [ ] **3.5** Create `appointments` table migration (depends on patients, facilities, doctors, service_offerings, availability_slots, service_workflows)
- [ ] **3.6** Create `appointment_steps` table migration (depends on appointments, workflow_steps, step_templates)
- [ ] **3.7** Run migrations: `php artisan migrate`
- [ ] **3.8** Create factories and seeders

## Detailed Table Specifications

### facilities
```php
Schema::create('facilities', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('address')->nullable();
    $table->string('phone', 50)->nullable();
    $table->json('meta')->nullable();
    $table->string('locale', 10)->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### doctors
```php
Schema::create('doctors', function (Blueprint $table) {
    $table->id();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('display_name');
    $table->string('npi', 50)->nullable();
    $table->string('specialty')->nullable();
    $table->text('profile')->nullable();
    $table->json('contact')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### services
```php
Schema::create('services', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('code', 50)->nullable();
    $table->enum('code_system', ['CPT', 'HCPCS', 'ICD', 'Custom'])->default('CPT');
    $table->string('code_version', 50)->nullable();
    $table->integer('default_duration_minutes')->default(30);
    $table->string('category')->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### step_templates
```php
Schema::create('step_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->integer('default_duration_minutes');
    $table->boolean('requires_location')->default(true);
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

### insurance_providers
```php
Schema::create('insurance_providers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('website')->nullable();
    $table->json('contact')->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

### facility_doctors (pivot)
```php
Schema::create('facility_doctors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('facility_id')->constrained()->onDelete('restrict');
    $table->foreignId('doctor_id')->constrained()->onDelete('restrict');
    $table->string('role', 100)->nullable();
    $table->boolean('active')->default(true);
    $table->timestamps();
    $table->unique(['facility_id', 'doctor_id']);
});
```

### insurance_plans
```php
Schema::create('insurance_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('insurance_provider_id')->constrained()->onDelete('cascade');
    $table->string('plan_code', 100);
    $table->string('name');
    $table->date('effective_from')->nullable();
    $table->date('effective_to')->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
    $table->unique(['insurance_provider_id', 'plan_code']);
});
```

### facility_insurance_plans
```php
Schema::create('facility_insurance_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->foreignId('insurance_plan_id')->constrained()->onDelete('cascade');
    $table->boolean('enabled')->default(true);
    $table->text('notes')->nullable();
    $table->date('accepted_since')->nullable();
    $table->timestamps();
    $table->unique(['facility_id', 'insurance_plan_id']);
});
```

### service_offerings
```php
Schema::create('service_offerings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('service_id')->constrained()->onDelete('cascade');
    $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->boolean('active')->default(true);
    $table->integer('default_duration_minutes')->nullable();
    $table->enum('visibility', ['public', 'private'])->default('public');
    $table->json('meta')->nullable();
    $table->timestamps();
    $table->unique(['service_id', 'doctor_id', 'facility_id']);
    $table->index(['facility_id', 'doctor_id']);
});
```

### service_workflows
```php
Schema::create('service_workflows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('service_offering_id')->constrained()->onDelete('cascade');
    $table->foreignId('insurance_plan_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('name');
    $table->integer('total_estimated_minutes');
    $table->boolean('active')->default(true);
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

### workflow_steps
```php
Schema::create('workflow_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('service_workflow_id')->constrained()->onDelete('cascade');
    $table->foreignId('step_template_id')->constrained()->onDelete('cascade');
    $table->integer('position');
    $table->integer('duration_minutes')->nullable();
    $table->string('location_type', 50)->nullable();
    $table->boolean('requires_preparation')->default(false);
    $table->boolean('can_be_skipped')->default(false);
    $table->json('meta')->nullable();
    $table->timestamps();
    $table->unique(['service_workflow_id', 'position']);
    $table->index(['service_workflow_id', 'position']);
});
```

### availability_rules
```php
Schema::create('availability_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->foreignId('service_offering_id')->nullable()->constrained()->onDelete('cascade');
    $table->tinyInteger('day_of_week'); // 0=Sun, 6=Sat
    $table->time('start_time');
    $table->time('end_time');
    $table->integer('slot_duration_minutes');
    $table->integer('slot_interval_minutes')->nullable();
    $table->boolean('active')->default(true);
    $table->json('meta')->nullable();
    $table->timestamps();
    $table->index(['doctor_id', 'facility_id']);
});
```

### availability_exceptions
```php
Schema::create('availability_exceptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('availability_rule_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
    $table->datetime('start_at');
    $table->datetime('end_at');
    $table->enum('type', ['blocked', 'override', 'emergency']);
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

### availability_slots
```php
Schema::create('availability_slots', function (Blueprint $table) {
    $table->id();
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
    $table->foreignId('service_offering_id')->nullable()->constrained()->onDelete('cascade');
    $table->datetime('start_at');
    $table->datetime('end_at');
    $table->enum('status', ['open', 'reserved', 'booked', 'cancelled'])->default('open');
    $table->integer('capacity')->default(1);
    $table->datetime('reserved_until')->nullable();
    $table->foreignId('created_from_rule_id')->nullable()->constrained('availability_rules')->onDelete('set null');
    $table->timestamps();
    $table->index('start_at');
    $table->index(['doctor_id', 'start_at']);
    $table->index(['facility_id', 'start_at']);
});
```

### patients
```php
Schema::create('patients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('first_name');
    $table->string('last_name');
    $table->string('email');
    $table->string('phone')->nullable();
    $table->date('dob')->nullable();
    $table->foreignId('default_insurance_plan_id')->nullable()->constrained()->onDelete('set null');
    $table->string('preferred_language', 10)->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index('email');
});
```

### appointments
```php
Schema::create('appointments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id')->constrained()->onDelete('cascade');
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
    $table->foreignId('service_offering_id')->constrained()->onDelete('cascade');
    $table->foreignId('availability_slot_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('service_workflow_id')->constrained()->onDelete('cascade');
    $table->datetime('start_at');
    $table->datetime('end_at');
    $table->enum('status', ['scheduled', 'checked_in', 'in_progress', 'completed', 'no_show', 'cancelled'])->default('scheduled');
    $table->text('notes')->nullable();
    $table->string('language', 10)->nullable();
    $table->string('locale', 10)->nullable();
    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();
    $table->index('patient_id');
    $table->index(['doctor_id', 'start_at']);
    $table->index(['facility_id', 'start_at']);
});
```

### appointment_steps
```php
Schema::create('appointment_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
    $table->foreignId('workflow_step_id')->constrained()->onDelete('cascade');
    $table->foreignId('step_template_id')->constrained()->onDelete('cascade');
    $table->integer('position');
    $table->datetime('scheduled_start_at');
    $table->datetime('scheduled_end_at');
    $table->enum('status', ['scheduled', 'completed', 'no_show', 'cancelled', 'in_progress'])->default('scheduled');
    $table->text('notes')->nullable();
    $table->string('location', 255)->nullable();
    $table->timestamps();
    $table->index(['appointment_id', 'position']);
});
```

## Verification Steps
- [ ] Run `php artisan migrate:fresh` to verify all migrations work
- [ ] Run `php artisan db:seed` to verify seeders work
- [ ] Check all foreign key constraints are correct
- [ ] Verify indexes are created properly
- [ ] Test soft deletes work on relevant tables
