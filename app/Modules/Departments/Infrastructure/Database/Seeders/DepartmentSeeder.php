<?php

namespace App\Modules\Departments\Infrastructure\Database\Seeders;

use App\Modules\Departments\Infrastructure\Database\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seed the core operational departments.
     * Staff members will be assigned to these departments by department_id.
     */
    public function run(): void
    {
        $departments = [
            [
                'name_ar'     => 'المالية',
                'name_en'     => 'Finance',
                'description' => 'Handles settlements, collections, and financial reports.',
                'is_active'   => true,
            ],
            [
                'name_ar'     => 'العمليات',
                'name_en'     => 'Operations',
                'description' => 'Oversees daily shipment operations and agent assignments.',
                'is_active'   => true,
            ],
            [
                'name_ar'     => 'الدعم',
                'name_en'     => 'Support',
                'description' => 'Handles customer and agent escalations and complaints.',
                'is_active'   => true,
            ],
            [
                'name_ar'     => 'اللوجستيات',
                'name_en'     => 'Logistics',
                'description' => 'Manages agent zones, routes, and delivery coordination.',
                'is_active'   => true,
            ],
            [
                'name_ar'     => 'تكنولوجيا المعلومات',
                'name_en'     => 'IT',
                'description' => 'Manages system infrastructure and technical support.',
                'is_active'   => true,
            ],
        ];

        foreach ($departments as $data) {
            $existing = Department::query()->where('name_en', $data['name_en'])->first();

            if ($existing) {
                $this->command->line("  Skipping department '{$data['name_en']}' (already exists).");
                continue;
            }

            Department::query()->create($data);
            $this->command->info("  Created department: {$data['name_en']} / {$data['name_ar']}");
        }
    }
}
