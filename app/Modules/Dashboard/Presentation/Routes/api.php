<?php

use App\Modules\Dashboard\Presentation\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('summary', [DashboardController::class, 'summary'])
                ->middleware('permission:dashboard.view');
            Route::get('shipments-chart', [DashboardController::class, 'shipmentsChart'])
                ->middleware('permission:dashboard.view');
            Route::get('top-agents', [DashboardController::class, 'topAgents'])
                ->middleware('permission:dashboard.view');
            Route::get('collections-balance', [DashboardController::class, 'collectionsBalance'])
                ->middleware('permission:dashboard.view');
            Route::get('delivery-performance', [DashboardController::class, 'deliveryPerformance'])
                ->middleware('permission:dashboard.view');
            Route::get('avg-delivery-time', [DashboardController::class, 'avgDeliveryTime'])
                ->middleware('permission:dashboard.view');
            Route::get('recent-orders', [DashboardController::class, 'recentOrders'])
                ->middleware('permission:dashboard.view');
        });
    });
