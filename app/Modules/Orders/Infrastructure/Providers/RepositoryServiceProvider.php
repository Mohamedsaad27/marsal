<?php

namespace App\Modules\Orders\Infrastructure\Providers;

use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Persistence\Repositories\AgentOrderRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AgentOrderRepositoryInterface::class,
            AgentOrderRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}