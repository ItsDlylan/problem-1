<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InsurancePlan;
use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

/**
 * Seeder for insurance_plans table.
 * Creates insurance plans for each provider.
 */
final class InsurancePlanSeeder extends Seeder
{
    public function run(): void
    {
        $providers = InsuranceProvider::all();

        // Create 2-3 plans per provider
        foreach ($providers as $provider) {
            InsurancePlan::factory()
                ->count(rand(2, 3))
                ->create([
                    'insurance_provider_id' => $provider->id,
                ]);
        }
    }
}

