<?php

namespace App\Modules\Orders\Infrastructure\Providers;

use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use App\Modules\Orders\Domain\Interfaces\ApprovalRequestRepositoryInterface;
use App\Modules\Orders\Infrastructure\Persistence\Repositories\AdminOrderRepository;
use App\Modules\Orders\Infrastructure\Persistence\Repositories\AgentOrderRepository;
use App\Modules\Orders\Infrastructure\Persistence\Repositories\ApprovalRequestRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AgentOrderRepositoryInterface::class,
            AgentOrderRepository::class,
        );

        $this->app->bind(
            AdminOrderRepositoryInterface::class,
            AdminOrderRepository::class,
        );

        $this->app->bind(
            ApprovalRequestRepositoryInterface::class,
            ApprovalRequestRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}