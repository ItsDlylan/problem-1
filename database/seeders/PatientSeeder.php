<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\InsurancePlan;
use Illuminate\Database\Seeder;

/**
 * Seeder for patients table.
 * Creates sample patients with insurance plans.
 */
final class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $insurancePlans = InsurancePlan::all();

        // Create 20 sample patients
        Patient::factory()->count(20)->create(function () use ($insurancePlans) {
            return [
                'default_insurance_plan_id' => $insurancePlans->random()->id,
            ];
        });
    }
}

