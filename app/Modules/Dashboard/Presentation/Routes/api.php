<?php

use App\Modules\Dashboard\Presentation\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('summary', [DashboardController::class, 'summary']);
            Route::get('shipments-chart', [DashboardController::class, 'shipmentsChart']);
            Route::get('top-agents', [DashboardController::class, 'topAgents']);
            Route::get('collections-balance', [DashboardController::class, 'collectionsBalance']);
            Route::get('delivery-performance', [DashboardController::class, 'deliveryPerformance']);
            Route::get('avg-delivery-time', [DashboardController::class, 'avgDeliveryTime']);
            Route::get('recent-orders', [DashboardController::class, 'recentOrders']);
        });
    });
