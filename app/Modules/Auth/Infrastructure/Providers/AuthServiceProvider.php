<?php

namespace App\Modules\Auth\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'auth_module');

        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../Presentation/Resources/Lang', 'auth');
        $this->loadViewsFrom(__DIR__ . '/../../Presentation/Resources/views', 'auth');
    }
}
