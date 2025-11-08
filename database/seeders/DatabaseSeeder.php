<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main database seeder.
 * Calls all seeders in the correct order based on foreign key dependencies.
 * Order matches the migration creation order from the planning document.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Day 1: Core reference tables (no dependencies)
        $this->call([
            FacilitySeeder::class,
            DoctorSeeder::class,
            ServiceSeeder::class,
            StepTemplateSeeder::class,
            InsuranceProviderSeeder::class,
        ]);

        // Day 2: Dependent tables (relate to core tables)
        $this->call([
            FacilityDoctorSeeder::class, // Depends on facilities, doctors
            FacilityUserSeeder::class, // Depends on facilities, doctors (via facility_doctors)
            InsurancePlanSeeder::class, // Depends on insurance_providers
            FacilityInsurancePlanSeeder::class, // Depends on facilities, insurance_plans
            ServiceOfferingSeeder::class, // Depends on services, doctors, facilities
            ServiceWorkflowSeeder::class, // Depends on service_offerings, insurance_plans
            WorkflowStepSeeder::class, // Depends on service_workflows, step_templates
        ]);

        // Day 3: Scheduling & Booking Tables
        $this->call([
            AvailabilityRuleSeeder::class, // Depends on doctors, facilities, service_offerings
            AvailabilityExceptionSeeder::class, // Depends on availability_rules, facilities, doctors
            AvailabilitySlotSeeder::class, // Depends on facilities, doctors, service_offerings, availability_rules
            PatientSeeder::class, // Depends on insurance_plans
            AppointmentSeeder::class, // Depends on patients, facilities, doctors, service_offerings, availability_slots, service_workflows
            AppointmentStepSeeder::class, // Depends on appointments, workflow_steps, step_templates
        ]);
    }
}
