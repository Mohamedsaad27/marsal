<?php

namespace App\Modules\Users\Infrastructure\Database\Seeders;

use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ShippingCompanySeeder extends Seeder
{
    /**
     * Seed 5 shipping companies.
     * Each company gets a users row + shipping_companies row + role assignment.
     */
    public function run(): void
    {
        $companies = [
            [
                'user' => [
                    'name'     => 'Ahmed Hassan',
                    'email'    => 'company1@marsal.test',
                    'phone'    => '01011100001',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'company' => [
                    'company_name'     => 'سريع للشحن',
                    'commercial_reg'   => 'EG-2021-11001',
                    'commission_type'  => 1,    // percentage
                    'commission_value' => 8.50,
                    'balance'          => 12500.00,
                    'is_active'        => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Sara Ibrahim',
                    'email'    => 'company2@marsal.test',
                    'phone'    => '01011100002',
                    'gender'   => 'female',
                    'is_active' => true,
                ],
                'company' => [
                    'company_name'     => 'النيل للتوصيل',
                    'commercial_reg'   => 'EG-2020-22002',
                    'commission_type'  => 1,
                    'commission_value' => 7.00,
                    'balance'          => 8750.00,
                    'is_active'        => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Mohamed Ali',
                    'email'    => 'company3@marsal.test',
                    'phone'    => '01011100003',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'company' => [
                    'company_name'     => 'الأمل للشحن',
                    'commercial_reg'   => 'EG-2019-33003',
                    'commission_type'  => 2,    // fixed
                    'commission_value' => 15.00,
                    'balance'          => 15000.00,
                    'is_active'        => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Fatima Nour',
                    'email'    => 'company4@marsal.test',
                    'phone'    => '01011100004',
                    'gender'   => 'female',
                    'is_active' => true,
                ],
                'company' => [
                    'company_name'     => 'إكسبريس مصر',
                    'commercial_reg'   => 'EG-2022-44004',
                    'commission_type'  => 1,
                    'commission_value' => 9.50,
                    'balance'          => 5500.00,
                    'is_active'        => 1,
                ],
            ],
            [
                'user' => [
                    'name'     => 'Karim Tarek',
                    'email'    => 'company5@marsal.test',
                    'phone'    => '01011100005',
                    'gender'   => 'male',
                    'is_active' => true,
                ],
                'company' => [
                    'company_name'     => 'جو شيب',
                    'commercial_reg'   => 'EG-2023-55005',
                    'commission_type'  => 1,
                    'commission_value' => 10.00,
                    'balance'          => 9200.00,
                    'is_active'        => 1,
                ],
            ],
        ];

        foreach ($companies as $data) {
            if (User::query()->where('email', $data['user']['email'])->exists()) {
                $this->command->line("  Skipping {$data['user']['email']} (already exists).");
                continue;
            }

            $user = User::query()->create([
                ...$data['user'],
                'password' => Hash::make('Password@123'),
            ]);

            ShippingCompany::query()->create([
                ...$data['company'],
                'user_id' => $user->user_id,
            ]);

            $user->assignRole('shipping_company');

            $this->command->info("  Created shipping company: {$data['company']['company_name']}");
        }
    }
}
