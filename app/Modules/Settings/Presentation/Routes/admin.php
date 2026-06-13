<?php

use App\Modules\Settings\Presentation\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin/settings')
    ->middleware(['auth:api'])
    ->group(function () {

        Route::get('/', [SettingsController::class, 'show'])
            ->middleware('permission:settings.view')
            ->name('admin.settings.show');

        // Changed from PUT to POST to support multipart/form-data for logo upload
        Route::post('/', [SettingsController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('admin.settings.update');
    });
