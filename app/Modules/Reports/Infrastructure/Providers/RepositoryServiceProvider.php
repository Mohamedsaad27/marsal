<?php

namespace App\Modules\Reports\Infrastructure\Providers;

use App\Modules\Reports\Domain\Interfaces\ReportsRepositoryInterface;
use App\Modules\Reports\Infrastructure\Persistence\Repositories\ReportsRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ReportsRepositoryInterface::class,
            ReportsRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
