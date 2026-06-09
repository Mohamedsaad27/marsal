<?php

namespace App\Modules\Users\Infrastructure\Database\Seeders;

use App\Modules\Departments\Infrastructure\Database\Models\Department;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffMemberSeeder extends Seeder
{
    /**
     * Seed 5 staff members across different departments.
     *
     * NOTE: The original staff_members migration had `department` (string),
     * but migration 2026_06_09_000002 dropped that column and added
     * `department_id` (UUID FK → departments). We therefore resolve each
     * department by name and use its UUID as the FK.
     *
     * We insert via DB::table() with an explicit UUID because the StaffMember
     * model fillable only guards model-level mass assignment — we need direct
     * access to the `department_id` FK column.
     */
    public function run(): void
    {
        // Pre-load department IDs keyed by English name
        $departmentMap = Department::query()
            ->where('is_active', true)
            ->pluck('department_id', 'name_en');

        if ($departmentMap->isEmpty()) {
            $this->command->error('No departments found. Run DepartmentSeeder first.');
            return;
        }

        $members = [
            [
                'user' => [
                    'name'     => 'Layla Ahmed',
                    'email'    => 'staff.finance@marsal.test',
                    'phone'    => '01033300001',
                    'gender'   => 'female',
                    'is_active' => true,
                ],
                'staff' => [
                    'department_name' => 'Finance',
                    'job_title'       => 'Financial Analyst',
                    'notes'           => 'Handles settlements and collections reports.',
                ],
            ],
            [
                'user' => [
                    'name'     => 'Omar Ibrahim',
                    'email'    => 'staff.ops@marsal.test',
                    'phone'    => '01033300002',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'staff' => [
                    'department_name' => 'Operations',
                    'job_title'       => 'Operations Manager',
                    'notes'           => 'Oversees daily shipment operations.',
                ],
            ],
            [
                'user' => [
                    'name'     => 'Nadia Salem',
                    'email'    => 'staff.support@marsal.test',
                    'phone'    => '01033300003',
                    'gender'   => 'female',
                    'is_active' => true,
                ],
                'staff' => [
                    'department_name' => 'Support',
                    'job_title'       => 'Customer Support Lead',
                    'notes'           => 'Handles escalations and agent complaints.',
                ],
            ],
            [
                'user' => [
                    'name'     => 'Khaled Hassan',
                    'email'    => 'staff.logistics@marsal.test',
                    'phone'    => '01033300004',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'staff' => [
                    'department_name' => 'Logistics',
                    'job_title'       => 'Logistics Coordinator',
                    'notes'           => 'Coordinates agent zones and assignments.',
                ],
            ],
            [
                'user' => [
                    'name'     => 'Rania Mostafa',
                    'email'    => 'staff.ops2@marsal.test',
                    'phone'    => '01033300005',
                    'gender'   => 'female',
                    'is_active' => true,
                ],
                'staff' => [
                    'department_name' => 'Operations',
                    'job_title'       => 'Operations Supervisor',
                    'notes'           => null,
                ],
            ],
        ];

        foreach ($members as $data) {
            if (User::query()->where('email', $data['user']['email'])->exists()) {
                $this->command->line("  Skipping {$data['user']['email']} (already exists).");
                continue;
            }

            $user = User::query()->create([
                ...$data['user'],
                'password' => Hash::make('Password@123'),
            ]);

            $deptId = $departmentMap->get($data['staff']['department_name']);

            $now = now()->toDateTimeString();
            DB::table('staff_members')->insert([
                'staff_member_id' => Str::uuid()->toString(),
                'user_id'         => $user->user_id,
                'department_id'   => $deptId,      // correct FK column post-migration
                'job_title'       => $data['staff']['job_title'],
                'notes'           => $data['staff']['notes'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $user->assignRole('staff_member');

            $dept = $data['staff']['department_name'];
            $this->command->info("  Created staff member: {$data['user']['name']} ({$dept})");
        }
    }
}
