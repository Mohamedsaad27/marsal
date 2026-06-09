<?php

namespace Database\Seeders;

use App\Modules\Departments\Infrastructure\Database\Seeders\DepartmentSeeder;
use App\Modules\Locations\Infrastructure\Database\Seeders\EgyptLocationsSeeder;
use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Infrastructure\Database\Seeders\DeliveryAgentSeeder;
use App\Modules\Users\Infrastructure\Database\Seeders\ShippingCompanySeeder;
use App\Modules\Users\Infrastructure\Database\Seeders\StaffMemberSeeder;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,   // roles + permissions (required first)
            EgyptLocationsSeeder::class,         // governorates + cities (required for order addresses)
            SuperAdminSeeder::class,             // super-admin user
            DepartmentSeeder::class,             // 5 departments (Finance, Operations, Support, Logistics, IT)
            ShippingCompanySeeder::class,        // 5 shipping companies
            DeliveryAgentSeeder::class,          // 10 delivery agents (2 supervisors + 8 regular)
            StaffMemberSeeder::class,            // 5 staff members → needs departments
            OrderSeeder::class,                  // ~170 orders with all sub-records
        ]);
    }
}
