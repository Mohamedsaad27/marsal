<?php

namespace App\Modules\AuditLog\Infrastructure\Providers;

use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Modules\AuditLog\Infrastructure\Persistence\AuditLogRepository;
use Illuminate\Support\ServiceProvider;

class AuditLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);

        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../../Presentation/Resources/Lang', 'audit_logs');
    }
}
