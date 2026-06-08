<?php

namespace App\Modules\Users\Infrastructure\Providers;

use App\Modules\Users\Domain\Interfaces\DeliveryAgentRepositoryInterface;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Persistence\DeliveryAgentRepository;
use App\Modules\Users\Infrastructure\Persistence\UserRepository;
use Illuminate\Support\ServiceProvider;

class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(DeliveryAgentRepositoryInterface::class, DeliveryAgentRepository::class);

        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../Presentation/Resources/Lang', 'users');
    }
}
