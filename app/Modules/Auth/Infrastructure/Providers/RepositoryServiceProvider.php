<?php

namespace App\Modules\Auth\Infrastructure\Providers;

use App\Modules\Auth\Domain\Interfaces\PasswordResetOtpRepositoryInterface;
use App\Modules\Auth\Domain\Services\WhatsAppService;
use App\Modules\Auth\Infrastructure\Persistence\PasswordResetOtpRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PasswordResetOtpRepositoryInterface::class, PasswordResetOtpRepository::class);
        $this->app->singleton(WhatsAppService::class);
    }
}
