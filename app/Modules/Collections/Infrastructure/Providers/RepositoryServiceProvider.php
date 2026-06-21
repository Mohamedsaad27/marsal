<?php

namespace App\Modules\Collections\Infrastructure\Providers;

use App\Modules\Collections\Domain\Interfaces\AgentCollectionRepositoryInterface;
use App\Modules\Collections\Infrastructure\Persistence\Repositories\AgentCollectionRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AgentCollectionRepositoryInterface::class,
            AgentCollectionRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
