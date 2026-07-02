<?php

use App\Modules\Collections\Presentation\Http\Controllers\AdminCollectionsController;
use App\Modules\Collections\Presentation\Http\Controllers\AdminSettlementsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('collections/stats', [AdminCollectionsController::class, 'stats'])
            ->middleware('permission:collections.view')
            ->name('admin.collections.stats');

        Route::get('collections', [AdminCollectionsController::class, 'index'])
            ->middleware('permission:collections.view')
            ->name('admin.collections.index');

        Route::patch('collections/{collectionId}/mark-cash-received', [AdminCollectionsController::class, 'markCashReceived'])
            ->middleware('permission:collections.create')
            ->name('admin.collections.mark-cash-received');

        Route::get('settlements/stats', [AdminSettlementsController::class, 'stats'])
            ->middleware('permission:settlements.view')
            ->name('admin.settlements.stats');

        Route::get('settlements', [AdminSettlementsController::class, 'index'])
            ->middleware('permission:settlements.view')
            ->name('admin.settlements.index');

        Route::post('settlements', [AdminSettlementsController::class, 'store'])
            ->middleware('permission:settlements.create')
            ->name('admin.settlements.store');

        Route::patch('settlements/{settlementId}/approve', [AdminSettlementsController::class, 'approve'])
            ->middleware('permission:settlements.approve')
            ->name('admin.settlements.approve');

        Route::patch('settlements/{settlementId}/mark-paid', [AdminSettlementsController::class, 'markPaid'])
            ->middleware('permission:settlements.mark_paid')
            ->name('admin.settlements.mark-paid');
    });
