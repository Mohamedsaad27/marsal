<?php

namespace App\Modules\Returns\Infrastructure\Providers;

use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use App\Modules\Returns\Infrastructure\Persistence\Repositories\ReturnRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ReturnRepositoryInterface::class,
            ReturnRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
