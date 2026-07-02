<?php

namespace App\Modules\Chat\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/agent.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/company.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/admin.php');
    }
}
