<?php

namespace App\Modules\Orders\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Orders\Domain\Interfaces\OrdersRepositoryInterface;
use App\Modules\Orders\Infrastructure\Persistence\Repositories\OrdersRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        
    }

    public function boot(): void
    {
        //
    }
}