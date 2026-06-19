<?php

namespace App\Modules\Notifications\Infrastructure\Providers;

use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notifications\Infrastructure\Persistence\Repositories\NotificationRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}