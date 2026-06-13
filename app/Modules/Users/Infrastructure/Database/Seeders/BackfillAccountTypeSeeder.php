<?php

namespace App\Modules\Users\Infrastructure\Database\Seeders;

use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Seeder;

class BackfillAccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->with(['roles', 'staffMember', 'shippingCompany', 'deliveryAgent'])
            ->whereNull('account_type')
            ->each(function (User $user) {
                $type = $user->resolveAccountType();

                if ($type !== null) {
                    $user->updateQuietly(['account_type' => $type->value]);
                }
            });
    }
}
