<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

/**
 * Seeder for facilities table.
 * Creates sample medical facilities with realistic data.
 */
final class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 sample facilities
        Facility::factory()->count(5)->create();
    }
}

