<?php

use App\Modules\Locations\Presentation\Http\Controllers\Admin\CityController;
use App\Modules\Locations\Presentation\Http\Controllers\Admin\GovernorateController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('governorates', [GovernorateController::class, 'index'])
            ->middleware('permission:governorates.view');
        Route::get('governorates/{governorateId}', [GovernorateController::class, 'show'])
            ->middleware('permission:governorates.view');
        Route::post('governorates', [GovernorateController::class, 'store'])
            ->middleware('permission:governorates.manage');
        Route::put('governorates/{governorateId}', [GovernorateController::class, 'update'])
            ->middleware('permission:governorates.manage');
        Route::patch('governorates/{governorateId}/toggle-status', [GovernorateController::class, 'toggleStatus'])
            ->middleware('permission:governorates.manage');
        Route::delete('governorates/{governorateId}', [GovernorateController::class, 'destroy'])
            ->middleware('permission:governorates.manage');
        Route::delete('governorates', [GovernorateController::class, 'bulkDestroy'])
            ->middleware('permission:governorates.manage');
        Route::get('governorates/{governorateId}/cities', [GovernorateController::class, 'cities'])
            ->middleware('permission:governorates.view');

        Route::get('cities', [CityController::class, 'index'])
            ->middleware('permission:governorates.view');
        Route::get('cities/{cityId}', [CityController::class, 'show'])
            ->middleware('permission:governorates.view');
        Route::post('cities', [CityController::class, 'store'])
            ->middleware('permission:governorates.manage');
        Route::put('cities/{cityId}', [CityController::class, 'update'])
            ->middleware('permission:governorates.manage');
        Route::patch('cities/{cityId}/toggle-status', [CityController::class, 'toggleStatus'])
            ->middleware('permission:governorates.manage');
        Route::delete('cities/{cityId}', [CityController::class, 'destroy'])
            ->middleware('permission:governorates.manage');
        Route::delete('cities', [CityController::class, 'bulkDestroy'])
            ->middleware('permission:governorates.manage');
    });
