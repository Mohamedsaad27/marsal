<?php

use App\Modules\Roles\Presentation\Http\Controllers\PermissionController;
use App\Modules\Roles\Presentation\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
        Route::put('roles/{id}', [RoleController::class, 'update'])->middleware('permission:roles.update');
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');
        Route::put('roles/{id}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:roles.update');

        Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view');
        Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create');
        Route::put('permissions/{id}', [PermissionController::class, 'update'])->middleware('permission:permissions.update');
        Route::delete('permissions/{id}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete');
    });
