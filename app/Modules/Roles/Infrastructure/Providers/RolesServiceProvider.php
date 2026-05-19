<?php

namespace App\Modules\Roles\Infrastructure\Providers;

use App\Modules\Roles\Domain\Interfaces\PermissionRepositoryInterface;
use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use App\Modules\Roles\Infrastructure\Persistence\PermissionRepository;
use App\Modules\Roles\Infrastructure\Persistence\RoleRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

class RolesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);

        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../Presentation/Resources/Lang', 'roles');

        Route::aliasMiddleware('role', RoleMiddleware::class);
        Route::aliasMiddleware('permission', PermissionMiddleware::class);

        Gate::before(function ($user, $ability) {
            return $user?->hasRole('super_admin') ? true : null;
        });
    }
}
