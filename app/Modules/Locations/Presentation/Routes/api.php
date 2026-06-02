<?php

use App\Modules\Locations\Presentation\Http\Controllers\LocationReferenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/locations')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('governorates', [LocationReferenceController::class, 'governorates'])
            ->middleware('permission:governorates.view');
        Route::get('governorates/{governorateId}/cities', [LocationReferenceController::class, 'cities'])
            ->middleware('permission:governorates.view');
    });
