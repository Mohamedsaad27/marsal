<?php

namespace App\Modules\Core\Infrastructure\Providers;

use App\Modules\Core\Domain\Interfaces\MediaRepositoryInterface;
use App\Modules\Core\Infrastructure\Persistence\Repositories\MediaRepository;
use App\Modules\Core\Infrastructure\Services\MediaStorageService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MediaStorageService::class);

        $this->app->bind(
            MediaRepositoryInterface::class,
            MediaRepository::class
        );
    }
}
