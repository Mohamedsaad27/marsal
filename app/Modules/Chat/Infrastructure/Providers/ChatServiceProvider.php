<?php

namespace App\Modules\Chat\Infrastructure\Providers;

use App\Modules\Chat\Infrastructure\Database\Models\Message;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../Presentation/Resources/Lang',
            'chat',
        );

        config([
            'core.media.owner_model_map.message' => Message::class,
        ]);
    }
}
