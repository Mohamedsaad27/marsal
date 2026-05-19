<?php

namespace App\Modules\Core\Infrastructure\Providers;

use App\Modules\Core\Presentation\Http\Middleware\ScopeTenant;
use App\Modules\Core\Presentation\Http\Middleware\SetLocale;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Route middleware aliases owned by the Core module.
     *
     * @var array<string, class-string>
     */
    protected array $middlewareAliases = [
        'scope.tenant' => ScopeTenant::class,
        'set.locale'   => SetLocale::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'core'
        );

        $this->app->singleton(
            ExceptionHandler::class,
            \App\Modules\Core\Application\Exceptions\Handler::class
        );

        // 3. Auto-discover and register all module ServiceProviders
        $this->app->register(ModuleServiceProvider::class);

        // 4. Repository interface → implementation bindings
        $this->app->register(RepositoryServiceProvider::class);

        // 5. Core module routes (health-check, shared endpoints)
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerMorphMap();
        $this->registerMiddlewareAliases();
        $this->registerTranslations();
        $this->registerPublishables();
    }

    protected function registerMorphMap(): void
    {
        $map = config('core.media.owner_model_map', []);

        if ($map !== []) {
            Relation::morphMap($map, merge: true);
        }
    }

    protected function registerMiddlewareAliases(): void
    {
        /** @var Router $router */
        $router = $this->app->make('router');

        foreach ($this->middlewareAliases as $alias => $class) {
            $router->aliasMiddleware($alias, $class);
        }
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../Presentation/Resources/Lang',
            'core'
        );
    }

    protected function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('core.php'),
        ], 'core-config');

        $this->publishes([
            __DIR__ . '/../../Presentation/Resources/Lang' => lang_path('vendor/core'),
        ], 'core-lang');
    }
}