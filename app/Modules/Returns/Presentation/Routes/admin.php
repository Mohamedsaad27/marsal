<?php

use App\Modules\Returns\Presentation\Http\Controllers\AdminReturnController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api', 'role:super_admin'])
    ->group(function () {
        Route::get('returns/stats', [AdminReturnController::class, 'stats'])
             ->name('admin.returns.stats');
        Route::get('returns', [AdminReturnController::class, 'index'])
             ->name('admin.returns.index');
        Route::patch('returns/{returnId}/receive', [AdminReturnController::class, 'receive'])
             ->name('admin.returns.receive');
        Route::patch('returns/{returnId}/return-to-company', [AdminReturnController::class, 'returnToCompany'])
             ->name('admin.returns.return-to-company');
    });
