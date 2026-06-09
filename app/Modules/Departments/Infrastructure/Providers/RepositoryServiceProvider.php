<?php

namespace App\Modules\Departments\Infrastructure\Providers;

use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;
use App\Modules\Departments\Infrastructure\Persistence\DepartmentRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DepartmentRepositoryInterface::class, DepartmentRepository::class);
    }
}
