<?php

use App\Modules\Notifications\Presentation\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifications API Routes
|--------------------------------------------------------------------------
| Base path : /api/v1/notifications
| Middleware : auth:api — all routes require a valid JWT
*/

Route::prefix('api/v1')
    ->middleware('auth:api')
    ->group(function () {

        Route::get(
            'notifications/unread-count',
            [NotificationController::class, 'unreadCount']
        )->middleware('permission:notifications.view')
            ->name('notifications.unread-count');

        Route::patch(
            'notifications/read-all',
            [NotificationController::class, 'markAllRead']
        )->middleware('permission:notifications.view')
            ->name('notifications.read-all');

        Route::delete(
            'notifications/read',
            [NotificationController::class, 'deleteRead']
        )->middleware('permission:notifications.view')
            ->name('notifications.delete-read');

        Route::get(
            'notifications',
            [NotificationController::class, 'index']
        )->middleware('permission:notifications.view')
            ->name('notifications.index');

        Route::patch(
            'notifications/{notificationId}/read',
            [NotificationController::class, 'markRead']
        )->middleware('permission:notifications.view')
            ->name('notifications.mark-read');
    });
