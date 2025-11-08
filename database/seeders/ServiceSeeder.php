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
        // Define unique service names to prevent duplicates
        // Each service will be created with a unique name from this list
        $serviceNames = [
            'General Consultation',
            'Follow-up Visit',
            'Lab Work',
            'X-Ray',
            'MRI Scan',
            'Blood Test',
            'Physical Examination',
            'Vaccination',
        ];

        // Create each service uniquely to prevent duplicates
        // Use firstOrCreate to ensure we don't create duplicates if seeder runs multiple times
        foreach ($serviceNames as $serviceName) {
            Service::firstOrCreate(
                ['name' => $serviceName],
                Service::factory()->make([
                    'name' => $serviceName,
                ])->toArray()
            );
        }
    }
}

