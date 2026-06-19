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

        // عدد الإشعارات غير المقروءة (badge) — يجب أن يأتي قبل {notificationId}
        Route::get(
            'notifications/unread-count',
            [NotificationController::class, 'unreadCount']
        )->name('notifications.unread-count');

        // تحديد جميع الإشعارات كمقروءة
        Route::patch(
            'notifications/read-all',
            [NotificationController::class, 'markAllRead']
        )->name('notifications.read-all');

        // قائمة الإشعارات المرقّمة
        Route::get(
            'notifications',
            [NotificationController::class, 'index']
        )->name('notifications.index');

        // تحديد إشعار واحد كمقروء
        Route::patch(
            'notifications/{notificationId}/read',
            [NotificationController::class, 'markRead']
        )->name('notifications.mark-read');
    });