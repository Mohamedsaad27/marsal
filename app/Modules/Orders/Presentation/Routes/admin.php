<?php

use App\Modules\Orders\Presentation\Http\Controllers\OrderImportController;
use Illuminate\Support\Facades\Route;

// Admin-only order routes — protected by auth:api + super_admin middleware
Route::prefix('api/v1/admin')
    ->middleware(['auth:api', 'role:super_admin'])
    ->group(function () {
        Route::post('orders/import', [OrderImportController::class, 'store'])
             ->name('orders.import');
    });
