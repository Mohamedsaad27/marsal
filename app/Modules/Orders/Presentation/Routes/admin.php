<?php

use App\Modules\Orders\Presentation\Http\Controllers\AdminApprovalController;
use App\Modules\Orders\Presentation\Http\Controllers\AdminOrderController;
use App\Modules\Orders\Presentation\Http\Controllers\OrderImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api', 'role:super_admin'])
    ->group(function () {

        // Excel import
        Route::post('orders/import', [OrderImportController::class, 'store'])
             ->name('orders.import');

        // Orders — Shipments dashboard
        Route::get('orders/stats', [AdminOrderController::class, 'stats'])
             ->name('admin.orders.stats');
        Route::get('orders', [AdminOrderController::class, 'index'])
             ->name('admin.orders.index');
        Route::get('orders/{orderId}', [AdminOrderController::class, 'show'])
             ->name('admin.orders.show');
        Route::patch('orders/{orderId}/assign', [AdminOrderController::class, 'assign'])
             ->name('admin.orders.assign');

        // Approval Requests — Approvals dashboard
        Route::get('approval-requests/stats', [AdminApprovalController::class, 'stats'])
             ->name('admin.approval-requests.stats');
        Route::get('approval-requests', [AdminApprovalController::class, 'index'])
             ->name('admin.approval-requests.index');
        Route::get('approval-requests/{approvalRequestId}', [AdminApprovalController::class, 'show'])
             ->name('admin.approval-requests.show');
        Route::patch('approval-requests/{approvalRequestId}/review', [AdminApprovalController::class, 'review'])
             ->name('admin.approval-requests.review');
    });
