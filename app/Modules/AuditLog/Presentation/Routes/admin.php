<?php

use App\Modules\AuditLog\Presentation\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit_logs.view')
            ->name('admin.audit-logs.index');

        Route::get('audit-logs/{type}/{id}', [AuditLogController::class, 'forSubject'])
            ->middleware('permission:audit_logs.view')
            ->name('admin.audit-logs.for-subject');
    });
