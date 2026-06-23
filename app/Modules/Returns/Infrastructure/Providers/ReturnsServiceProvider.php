<?php

namespace App\Modules\Returns\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class ReturnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../Presentation/Resources/Lang',
            'returns'
        );
    }
}
