<?php

use App\Modules\Collections\Presentation\Http\Controllers\AgentCollectionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Collections API Routes
|--------------------------------------------------------------------------
| Base path : /api/v1/agent
| Middleware: auth:api + role:delivery_agent
*/

Route::prefix('api/v1/agent')
    ->middleware(['auth:api', 'role:delivery_agent'])
    ->group(function () {
        Route::get('collections/summary', [AgentCollectionsController::class, 'summary'])
            ->name('agent.collections.summary');

        Route::get('collections', [AgentCollectionsController::class, 'index'])
            ->name('agent.collections.index');
    });
