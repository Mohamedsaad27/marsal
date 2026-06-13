<?php

namespace App\Modules\Settings\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        Route::middleware('api')
            ->group(base_path('app/Modules/Settings/Presentation/Routes/admin.php'));
    }
}
