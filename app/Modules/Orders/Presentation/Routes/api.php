<?php

use App\Modules\Orders\Presentation\Http\Controllers\AgentDashboardController;
use App\Modules\Orders\Presentation\Http\Controllers\AgentDefinitionsController;
use App\Modules\Orders\Presentation\Http\Controllers\AgentOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Orders API Routes
|--------------------------------------------------------------------------
| Base path : /api/v1/agent
| Middleware: auth:api + role:delivery_agent
*/

Route::prefix('api/v1/agent')
    ->middleware(['auth:api', 'role:delivery_agent'])
    ->group(function () {
        Route::get('dashboard', [AgentDashboardController::class, 'index'])
            ->name('agent.dashboard');

        Route::get('definitions', [AgentDefinitionsController::class, 'index'])
            ->name('agent.definitions');

        Route::get('orders', [AgentOrderController::class, 'index'])
            ->name('agent.orders.index');

        Route::get('orders/{orderId}', [AgentOrderController::class, 'show'])
            ->name('agent.orders.show');

        Route::patch('orders/{orderId}/status', [AgentOrderController::class, 'updateStatus'])
            ->name('agent.orders.update-status');

        Route::post('orders/{orderId}/proof', [AgentOrderController::class, 'uploadProof'])
            ->name('agent.orders.upload-proof');
    });
