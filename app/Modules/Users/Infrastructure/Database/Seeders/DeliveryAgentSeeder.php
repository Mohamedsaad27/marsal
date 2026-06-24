<?php

namespace App\Modules\Users\Infrastructure\Database\Seeders;

use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeliveryAgentSeeder extends Seeder
{
    /**
     * Seed 10 delivery agents (2 supervisors + 8 regular).
     * Supervisors have supervisor_agent_id = null.
     * Regular agents point to one of the two supervisors.
     */
    public function run(): void
    {
        // ── 1. Supervisors ────────────────────────────────────────────────
        $supervisorData = [
            [
                'user' => [
                    'name'     => 'Hassan Mahmoud',
                    'email'    => 'agent.sup1@marsal.test',
                    'phone'    => '01022200001',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '29901011234501',
                    'vehicle_type'         => 2,   // car
                    'vehicle_plate_number' => 'أ.ب.ج 1234',
                    'commission_type'      => 2,
                    'commission_value'     => 5.00,
                    'balance'              => 3200.00,
                    'is_available'         => 1,
                    'supervisor_agent_id'  => null,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Sameh Adel',
                    'email'    => 'agent.sup2@marsal.test',
                    'phone'    => '01022200002',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '29805021234502',
                    'vehicle_type'         => 3,   // van
                    'vehicle_plate_number' => 'د.هـ.و 5678',
                    'commission_type'      => 2,
                    'commission_value'     => 5.50,
                    'balance'              => 2100.00,
                    'is_available'         => 1,
                    'supervisor_agent_id'  => null,
                ],
            ],
        ];

        $supervisors = [];
        foreach ($supervisorData as $data) {
            if (User::query()->where('email', $data['user']['email'])->exists()) {
                $this->command->line("  Skipping {$data['user']['email']} (already exists).");
                $user = User::query()->where('email', $data['user']['email'])->first();
                $supervisors[] = $user->deliveryAgent;
                continue;
            }

            $user = User::query()->create([
                ...$data['user'],
                'password' => Hash::make('Password@123'),
                'account_type' => AccountTypeEnum::DeliveryAgent->value,
            ]);

            $agent = DeliveryAgent::query()->create([
                ...$data['agent'],
                'user_id' => $user->user_id,
            ]);

            $user->assignRole('delivery_agent');
            $supervisors[] = $agent;

            $this->command->info("  Created supervisor agent: {$data['user']['name']}");
        }

        // ── 2. Regular agents ─────────────────────────────────────────────
        $regularAgents = [
            [
                'user' => [
                    'name'     => 'Mostafa Saeed',
                    'email'    => 'agent1@marsal.test',
                    'phone'    => '01022200003',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '30001031234503',
                    'vehicle_type'         => 1,   // motorcycle
                    'vehicle_plate_number' => 'ز.ح.ط 1111',
                    'commission_type'      => 2,
                    'commission_value'     => 4.00,
                    'balance'              => 850.00,
                    'is_available'         => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Youssef Nabil',
                    'email'    => 'agent2@marsal.test',
                    'phone'    => '01022200004',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '29911041234504',
                    'vehicle_type'         => 1,
                    'vehicle_plate_number' => 'ي.ك.ل 2222',
                    'commission_type'      => 2,
                    'commission_value'     => 4.50,
                    'balance'              => 620.00,
                    'is_available'         => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Amr Fouad',
                    'email'    => 'agent3@marsal.test',
                    'phone'    => '01022200005',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '30105051234505',
                    'vehicle_type'         => 1,
                    'vehicle_plate_number' => 'م.ن.هـ 3333',
                    'commission_type'      => 2,   // fixed
                    'commission_value'     => 10.00,
                    'balance'              => 400.00,
                    'is_available'         => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Omar Sherif',
                    'email'    => 'agent4@marsal.test',
                    'phone'    => '01022200006',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '30006061234506',
                    'vehicle_type'         => 4,   // bicycle
                    'vehicle_plate_number' => null,
                    'commission_type'      => 2,
                    'commission_value'     => 3.50,
                    'balance'              => 280.00,
                    'is_available'         => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Tarek Gamal',
                    'email'    => 'agent5@marsal.test',
                    'phone'    => '01022200007',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '29807071234507',
                    'vehicle_type'         => 1,
                    'vehicle_plate_number' => 'و.ز.ح 4444',
                    'commission_type'      => 2,
                    'commission_value'     => 5.00,
                    'balance'              => 1100.00,
                    'is_available'         => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Ibrahim Khalil',
                    'email'    => 'agent6@marsal.test',
                    'phone'    => '01022200008',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '29908081234508',
                    'vehicle_type'         => 1,
                    'vehicle_plate_number' => 'ط.ي.ك 5555',
                    'commission_type'      => 2,
                    'commission_value'     => 4.00,
                    'balance'              => 730.00,
                    'is_available'         => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Nada Ramzy',
                    'email'    => 'agent7@marsal.test',
                    'phone'    => '01022200009',
                    'gender'   => 'female',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '30009091234509',
                    'vehicle_type'         => 2,
                    'vehicle_plate_number' => 'ل.م.ن 6666',
                    'commission_type'      => 2,
                    'commission_value'     => 4.75,
                    'balance'              => 960.00,
                    'is_available'         => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Salma Khaled',
                    'email'    => 'agent8@marsal.test',
                    'phone'    => '01022200010',
                    'gender'   => 'female',
                    'is_active' => true,
                ],
                'agent' => [
                    'national_id'          => '30110101234510',
                    'vehicle_type'         => 5,   // on_foot
                    'vehicle_plate_number' => null,
                    'commission_type'      => 2,
                    'commission_value'     => 8.00,
                    'balance'              => 310.00,
                    'is_available'         => 1,
                ],
            ],
        ];

        foreach ($regularAgents as $index => $data) {
            if (User::query()->where('email', $data['user']['email'])->exists()) {
                $this->command->line("  Skipping {$data['user']['email']} (already exists).");
                continue;
            }

            // Alternate between the two supervisors
            $supervisor = $supervisors[$index % 2] ?? null;

            $user = User::query()->create([
                ...$data['user'],
                'password' => Hash::make('Password@123'),
                'account_type' => AccountTypeEnum::DeliveryAgent->value,
            ]);

            DeliveryAgent::query()->create([
                ...$data['agent'],
                'user_id'             => $user->user_id,
                'supervisor_agent_id' => $supervisor?->delivery_agent_id,
            ]);

            $user->assignRole('delivery_agent');

            $this->command->info("  Created delivery agent: {$data['user']['name']}");
        }
    }
}
