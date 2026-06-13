<?php

namespace App\Modules\Settings\Infrastructure\Providers;

use App\Modules\Settings\Domain\Interfaces\SettingRepositoryInterface;
use App\Modules\Settings\Infrastructure\Persistence\SettingRepository;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
