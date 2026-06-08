<?php

namespace App\Modules\Dashboard\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../Presentation/Resources/Lang', 'dashboard');
    }
}
