<?php

namespace Database\Seeders;

use App\Modules\Locations\Infrastructure\Database\Seeders\EgyptLocationsSeeder;
use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            EgyptLocationsSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
