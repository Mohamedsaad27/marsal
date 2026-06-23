<?php

namespace App\Modules\Users\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/agent.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/admin.php');
    }
}
