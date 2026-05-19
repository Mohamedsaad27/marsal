<?php

namespace App\Modules\Users\Infrastructure\Database\Seeders;

use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'Admin@123');

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $user = User::query()->create([
            'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
            'email' => $email,
            'phone' => '01098001021',
            'password' => Hash::make($password),
            'user_type' => AccountTypeEnum::SuperAdmin,
            'is_active' => true,
        ]);

        $user->assignRole('super_admin');
    }
}
