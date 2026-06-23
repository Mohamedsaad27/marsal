<?php

use App\Modules\Orders\Presentation\Http\Controllers\AgentDashboardController;
use App\Modules\Orders\Presentation\Http\Controllers\AgentDefinitionsController;
use App\Modules\Orders\Presentation\Http\Controllers\AgentOrderController;
use App\Modules\Orders\Presentation\Http\Controllers\AgentScheduleController;
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

        Route::patch('orders/{orderId}/reschedule', [AgentOrderController::class, 'reschedule'])
            ->name('agent.orders.reschedule');

        Route::post('orders/{orderId}/proof', [AgentOrderController::class, 'uploadProof'])
            ->name('agent.orders.upload-proof');

        Route::get('schedule/calendar', [AgentScheduleController::class, 'calendar'])
            ->name('agent.schedule.calendar');

        Route::get('schedule', [AgentScheduleController::class, 'index'])
            ->name('agent.schedule.index');
    });
