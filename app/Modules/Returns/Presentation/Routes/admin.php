<?php

use App\Modules\Returns\Presentation\Http\Controllers\AdminReturnController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('returns/stats', [AdminReturnController::class, 'stats'])
            ->middleware('permission:returns.view')
            ->name('admin.returns.stats');

        Route::get('returns', [AdminReturnController::class, 'index'])
            ->middleware('permission:returns.view')
            ->name('admin.returns.index');

        Route::patch('returns/{returnId}/receive', [AdminReturnController::class, 'receive'])
            ->middleware('permission:returns.receive')
            ->name('admin.returns.receive');

        Route::patch('returns/{returnId}/return-to-company', [AdminReturnController::class, 'returnToCompany'])
            ->middleware('permission:returns.send_to_company')
            ->name('admin.returns.return-to-company');
    });
