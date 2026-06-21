<?php

namespace App\Modules\Collections\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class CollectionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'collections',
        );

        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../Presentation/Resources/Lang',
            'collections',
        );
    }
}
