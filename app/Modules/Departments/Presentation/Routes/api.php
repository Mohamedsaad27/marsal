<?php

use App\Modules\Departments\Presentation\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('departments', [DepartmentController::class, 'index'])
            ->middleware('permission:departments.view');
        Route::post('departments', [DepartmentController::class, 'store'])
            ->middleware('permission:departments.manage');
        Route::get('departments/{departmentId}', [DepartmentController::class, 'show'])
            ->middleware('permission:departments.view');
        Route::put('departments/{departmentId}', [DepartmentController::class, 'update'])
            ->middleware('permission:departments.manage');
        Route::delete('departments/{departmentId}', [DepartmentController::class, 'destroy'])
            ->middleware('permission:departments.manage');
        Route::post('departments/{departmentId}/restore', [DepartmentController::class, 'restore'])
            ->middleware('permission:departments.manage');
    });
