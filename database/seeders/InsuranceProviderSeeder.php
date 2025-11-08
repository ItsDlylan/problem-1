<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

/**
 * Seeder for insurance_providers table.
 * Creates sample insurance providers.
 */
final class InsuranceProviderSeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 sample insurance providers
        InsuranceProvider::factory()->count(5)->create();
    }
}

