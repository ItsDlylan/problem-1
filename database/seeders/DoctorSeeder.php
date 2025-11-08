<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

/**
 * Seeder for doctors table.
 * Creates sample doctors with various specialties.
 */
final class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 sample doctors
        Doctor::factory()->count(10)->create();
    }
}

