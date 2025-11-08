<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Seeder for services table.
 * Creates common medical services.
 */
final class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Create 8 sample services
        Service::factory()->count(8)->create();
    }
}

