<?php

namespace App\Modules\Notifications\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Helpers\ApiResponse;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Notifications\Application\UseCases\DeleteReadNotificationsUseCase;
use App\Modules\Notifications\Application\UseCases\GetNotificationKpisUseCase;
use App\Modules\Notifications\Application\UseCases\GetUnreadCountUseCase;
use App\Modules\Notifications\Application\UseCases\GetUserNotificationsUseCase;
use App\Modules\Notifications\Application\UseCases\MarkAllNotificationsReadUseCase;
use App\Modules\Notifications\Application\UseCases\MarkNotificationReadUseCase;
use App\Modules\Notifications\Presentation\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private GetUserNotificationsUseCase    $getUserNotifications,
        private GetNotificationKpisUseCase     $getNotificationKpis,
        private GetUnreadCountUseCase          $getUnreadCount,
        private MarkNotificationReadUseCase    $markRead,
        private MarkAllNotificationsReadUseCase $markAllRead,
        private DeleteReadNotificationsUseCase  $deleteRead,
    ) {}

    /**
     * GET /api/v1/notifications
     * قائمة الإشعارات — مرقّمة، الأحدث أولاً
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $notifications = $this->getUserNotifications->execute(
            userId:  auth()->id(),
            perPage: $perPage,
        );

        return ApiResponse::success(
            array_merge(
                ['kpis' => $this->getNotificationKpis->execute(auth()->id())],
                ['items' => NotificationResource::collection($notifications->items())],
                PaginationMeta::getMeta($notifications),
            ),
            __('notifications::messages.list_success'),
        );
    }

    /**
     * GET /api/v1/notifications/unread-count
     * عدد الإشعارات غير المقروءة — لعرض الشارة (badge)
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->getUnreadCount->execute(auth()->id());

        return ApiResponse::success(
            data:    ['unread_count' => $count],
            message: __('notifications::messages.unread_count_success'),
        );
    }

    /**
     * PATCH /api/v1/notifications/{notificationId}/read
     * تحديد إشعار واحد كمقروء
     */
    public function markRead(string $notificationId): JsonResponse
    {
        $notification = $this->markRead->execute($notificationId, auth()->id());

        return ApiResponse::success(
            data:    new NotificationResource($notification),
            message: __('notifications::messages.marked_read'),
        );
    }

    /**
     * PATCH /api/v1/notifications/read-all
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllRead(): JsonResponse
    {
        $updated = $this->markAllRead->execute(auth()->id());

        return ApiResponse::success(
            data:    ['updated_count' => $updated],
            message: __('notifications::messages.all_marked_read'),
        );
    }

    /**
     * DELETE /api/v1/notifications/read
     * حذف جميع الإشعارات المقروءة
     */
    public function deleteRead(): JsonResponse
    {
        $deleted = $this->deleteRead->execute(auth()->id());

        return ApiResponse::success(
            data:    ['deleted_count' => $deleted],
            message: __('notifications::messages.read_deleted'),
        );
    }
}
