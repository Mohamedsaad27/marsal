<?php

use App\Modules\Collections\Presentation\Http\Controllers\AdminCollectionsController;
use App\Modules\Collections\Presentation\Http\Controllers\AdminSettlementsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api', 'role:super_admin'])
    ->group(function () {
        Route::get('collections/stats', [AdminCollectionsController::class, 'stats'])
            ->name('admin.collections.stats');

        Route::get('collections', [AdminCollectionsController::class, 'index'])
            ->name('admin.collections.index');

        Route::patch('collections/{collectionId}/mark-cash-received', [AdminCollectionsController::class, 'markCashReceived'])
            ->name('admin.collections.mark-cash-received');

        Route::get('settlements/stats', [AdminSettlementsController::class, 'stats'])
            ->name('admin.settlements.stats');

        Route::get('settlements', [AdminSettlementsController::class, 'index'])
            ->name('admin.settlements.index');

        Route::post('settlements', [AdminSettlementsController::class, 'store'])
            ->name('admin.settlements.store');

        Route::patch('settlements/{settlementId}/approve', [AdminSettlementsController::class, 'approve'])
            ->name('admin.settlements.approve');

        Route::patch('settlements/{settlementId}/mark-paid', [AdminSettlementsController::class, 'markPaid'])
            ->name('admin.settlements.mark-paid');
    });
