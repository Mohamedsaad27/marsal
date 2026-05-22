<?php

use App\Modules\Roles\Presentation\Http\Controllers\PermissionController;
use App\Modules\Roles\Presentation\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.manage');
        Route::put('roles/{id}', [RoleController::class, 'update'])->middleware('permission:roles.manage');
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:roles.manage');
        Route::put('roles/{id}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:roles.manage');

        Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:roles.view');
        Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:roles.manage');
        Route::put('permissions/{id}', [PermissionController::class, 'update'])->middleware('permission:roles.manage');
        Route::delete('permissions/{id}', [PermissionController::class, 'destroy'])->middleware('permission:roles.manage');
    });
