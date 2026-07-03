<?php

use App\Modules\Orders\Presentation\Http\Controllers\AdminApprovalController;
use App\Modules\Orders\Presentation\Http\Controllers\AdminOrderController;
use App\Modules\Orders\Presentation\Http\Controllers\OrderImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {

        Route::post('orders/import', [OrderImportController::class, 'store'])
            ->middleware('permission:orders.import')
            ->name('orders.import');

        Route::get('orders/stats', [AdminOrderController::class, 'stats'])
            ->middleware('permission:orders.view')
            ->name('admin.orders.stats');

        Route::get('orders/export', [AdminOrderController::class, 'export'])
            ->middleware('permission:orders.export')
            ->name('admin.orders.export');

        Route::get('orders', [AdminOrderController::class, 'index'])
            ->middleware('permission:orders.view')
            ->name('admin.orders.index');

        Route::get('orders/{orderId}', [AdminOrderController::class, 'show'])
            ->middleware('permission:orders.view')
            ->name('admin.orders.show');

        Route::patch('orders/{orderId}/assign', [AdminOrderController::class, 'assign'])
            ->middleware('permission:orders.assign')
            ->name('admin.orders.assign');

        Route::patch('orders/{orderId}/status', [AdminOrderController::class, 'updateStatus'])
            ->middleware('permission:orders.update')
            ->name('admin.orders.update-status');

        Route::delete('orders', [AdminOrderController::class, 'bulkDestroy'])
            ->middleware('permission:orders.delete')
            ->name('admin.orders.bulkDestroy');

        Route::get('approval-requests/stats', [AdminApprovalController::class, 'stats'])
            ->middleware('permission:approval_requests.view')
            ->name('admin.approval-requests.stats');

        Route::get('approval-requests', [AdminApprovalController::class, 'index'])
            ->middleware('permission:approval_requests.view')
            ->name('admin.approval-requests.index');

        Route::get('approval-requests/{approvalRequestId}', [AdminApprovalController::class, 'show'])
            ->middleware('permission:approval_requests.view')
            ->name('admin.approval-requests.show');

        Route::patch('approval-requests/{approvalRequestId}/review', [AdminApprovalController::class, 'review'])
            ->middleware('permission:approval_requests.approve|approval_requests.reject')
            ->name('admin.approval-requests.review');
    });
