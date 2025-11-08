<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\InsurancePlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder for facility_insurance_plans pivot table.
 * Links facilities to accepted insurance plans.
 */
final class FacilityInsurancePlanSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = Facility::all();
        $insurancePlans = InsurancePlan::all();

        // Link each facility to 2-5 insurance plans
        foreach ($facilities as $facility) {
            $planCount = rand(2, min(5, $insurancePlans->count()));
            $selectedPlans = $insurancePlans->random($planCount);

            foreach ($selectedPlans as $plan) {
                DB::table('facility_insurance_plans')->insert([
                    'facility_id' => $facility->id,
                    'insurance_plan_id' => $plan->id,
                    'enabled' => true,
                    'notes' => fake()->boolean(30) ? fake()->sentence() : null,
                    'accepted_since' => fake()->dateTimeBetween('-2 years', 'now'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

