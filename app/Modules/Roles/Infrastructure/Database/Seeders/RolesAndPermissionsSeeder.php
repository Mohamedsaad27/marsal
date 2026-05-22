<?php

namespace App\Modules\Roles\Infrastructure\Database\Seeders;

use App\Modules\Users\Domain\Enums\PermissionEnum;
use App\Modules\Users\Infrastructure\Database\Seeders\PermissionSeeder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $guard = 'api';

        foreach (['staff_members.view', 'staff_members.create', 'staff_members.update', 'staff_members.delete'] as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $staffMember = Role::query()->firstOrCreate(['name' => 'staff_member', 'guard_name' => $guard]);
        $staffMember->syncPermissions([
            PermissionEnum::UsersView->value,
            PermissionEnum::ShippingCompaniesView->value,
            PermissionEnum::DeliveryAgentsView->value,
            'staff_members.view',
            PermissionEnum::OrdersView->value,
            PermissionEnum::OrdersCreate->value,
            PermissionEnum::OrdersUpdate->value,
            PermissionEnum::OrdersAssign->value,
        ]);

        $superAdmin = Role::query()->where('name', 'super_admin')->where('guard_name', $guard)->first();
        $superAdmin?->givePermissionTo(['staff_members.view', 'staff_members.create', 'staff_members.update', 'staff_members.delete']);
    }
}
