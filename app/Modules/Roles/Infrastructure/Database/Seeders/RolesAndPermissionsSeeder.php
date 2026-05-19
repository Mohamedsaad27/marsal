<?php

namespace App\Modules\Roles\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api';

        $permissions = [
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'permissions.view', 'permissions.create', 'permissions.update', 'permissions.delete',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'shipping_companies.view', 'shipping_companies.create', 'shipping_companies.update', 'shipping_companies.delete',
            'delivery_agents.view', 'delivery_agents.create', 'delivery_agents.update', 'delivery_agents.delete',
            'orders.view', 'orders.create', 'orders.update', 'orders.assign',
        ];

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $superAdmin = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $superAdmin->syncPermissions(Permission::query()->where('guard_name', $guard)->pluck('name'));

        $company = Role::query()->firstOrCreate(['name' => 'shipping_company', 'guard_name' => $guard]);
        $company->syncPermissions([
            'orders.view', 'orders.create', 'orders.update',
            'shipping_companies.view',
        ]);

        $agent = Role::query()->firstOrCreate(['name' => 'delivery_agent', 'guard_name' => $guard]);
        $agent->syncPermissions([
            'orders.view', 'orders.update',
            'delivery_agents.view',
        ]);
    }
}
