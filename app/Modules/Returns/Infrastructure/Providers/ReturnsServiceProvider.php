<?php

namespace App\Modules\Returns\Infrastructure\Providers;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Returns\Infrastructure\Observers\OrderObserver;
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
        Order::observe(OrderObserver::class);

        $this->loadTranslationsFrom(
            __DIR__.'/../../Presentation/Resources/Lang',
            'returns'
        );
    }
}
