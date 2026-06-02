<?php

namespace App\Modules\Locations\Infrastructure\Providers;

use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Persistence\CityRepository;
use App\Modules\Locations\Infrastructure\Persistence\GovernorateRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GovernorateRepositoryInterface::class, GovernorateRepository::class);
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);
    }
}
