<?php

namespace App\Modules\Chat\Infrastructure\Providers;

use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use App\Modules\Chat\Infrastructure\Database\Models\Message;
use App\Modules\Chat\Infrastructure\Persistence\Repositories\ChatRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ChatRepositoryInterface::class,
            ChatRepository::class,
        );
    }
}
