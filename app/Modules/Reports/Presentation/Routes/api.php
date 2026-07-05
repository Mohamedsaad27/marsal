<?php

use App\Modules\Reports\Presentation\Http\Controllers\AdminReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin/reports')
    ->middleware(['auth:api', 'permission:reports.view'])
    ->group(function () {
        Route::get('orders', [AdminReportsController::class, 'orders'])->name('admin.reports.orders');
        Route::get('collections', [AdminReportsController::class, 'collections'])->name('admin.reports.collections');
        Route::get('settlements', [AdminReportsController::class, 'settlements'])->name('admin.reports.settlements');
        Route::get('delivery-agents', [AdminReportsController::class, 'deliveryAgents'])->name('admin.reports.delivery-agents');
        Route::get('shipping-companies', [AdminReportsController::class, 'shippingCompanies'])->name('admin.reports.shipping-companies');
    });
