<?php

namespace App\Modules\Roles\Infrastructure\Database\Seeders;

use App\Modules\Users\Domain\Enums\PermissionEnum;
use App\Modules\Users\Infrastructure\Database\Seeders\PermissionSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $guard = 'api';

        $staffMember = Role::query()->firstOrCreate(['name' => 'staff_member', 'guard_name' => $guard]);
        $staffMember->syncPermissions([
            PermissionEnum::UsersView->value,
            PermissionEnum::ShippingCompaniesView->value,
            PermissionEnum::DeliveryAgentsView->value,
            PermissionEnum::StaffMembersView->value,
            PermissionEnum::OrdersView->value,
            PermissionEnum::OrdersCreate->value,
            PermissionEnum::OrdersUpdate->value,
            PermissionEnum::OrdersAssign->value,
        ]);
    }
}
