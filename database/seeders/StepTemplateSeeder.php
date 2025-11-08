<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StepTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeder for step_templates table.
 * Creates common workflow step templates.
 */
final class StepTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Create 8 sample step templates
        StepTemplate::factory()->count(8)->create();
    }
}

