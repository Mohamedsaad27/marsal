<?php

namespace App\Modules\Collections\Infrastructure\Providers;

use App\Modules\Collections\Domain\Interfaces\AdminCollectionRepositoryInterface;
use App\Modules\Collections\Domain\Interfaces\AgentCollectionRepositoryInterface;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Persistence\Repositories\AdminCollectionRepository;
use App\Modules\Collections\Infrastructure\Persistence\Repositories\AgentCollectionRepository;
use App\Modules\Collections\Infrastructure\Persistence\Repositories\SettlementRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AgentCollectionRepositoryInterface::class,
            AgentCollectionRepository::class,
        );

        $this->app->bind(
            AdminCollectionRepositoryInterface::class,
            AdminCollectionRepository::class,
        );

        $this->app->bind(
            SettlementRepositoryInterface::class,
            SettlementRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
