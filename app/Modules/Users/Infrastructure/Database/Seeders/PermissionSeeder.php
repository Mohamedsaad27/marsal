<?php

namespace App\Modules\Users\Infrastructure\Database\Seeders;

use App\Modules\Users\Domain\Enums\PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api';

        foreach (PermissionEnum::values() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $superAdmin->syncPermissions(PermissionEnum::values());

        $shippingCompany = Role::firstOrCreate(['name' => 'shipping_company', 'guard_name' => $guard]);
        $shippingCompany->syncPermissions([
            PermissionEnum::OrdersView->value,
            PermissionEnum::OrdersCreate->value,
            PermissionEnum::OrdersViewFinancials->value,
            PermissionEnum::ApprovalRequestsView->value,
            PermissionEnum::ApprovalRequestsApprove->value,
            PermissionEnum::ApprovalRequestsReject->value,
            PermissionEnum::ReturnsView->value,
            PermissionEnum::SettlementsView->value,
            PermissionEnum::ChatView->value,
            PermissionEnum::ChatSend->value,
            PermissionEnum::NotificationsView->value,
        ]);

        $deliveryAgent = Role::firstOrCreate(['name' => 'delivery_agent', 'guard_name' => $guard]);
        $deliveryAgent->syncPermissions([
            PermissionEnum::OrdersView->value,
            PermissionEnum::CollectionsCreate->value,
            PermissionEnum::ReturnsView->value,
            PermissionEnum::ChatView->value,
            PermissionEnum::ChatSend->value,
            PermissionEnum::NotificationsView->value,
        ]);
    }
}
