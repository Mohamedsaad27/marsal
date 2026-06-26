<?php

namespace App\Modules\Orders\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/company.php');
    }
}