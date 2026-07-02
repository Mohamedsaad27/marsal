<?php

namespace App\Modules\Notifications\Infrastructure\Persistence\Repositories;

use App\Modules\Notifications\Domain\Enums\NotificationKpiCategoryEnum;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notifications\Infrastructure\Database\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $data): array
    {
        return Notification::create($data)->toArray();
    }

    public function getForUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function markAsRead(string $notificationId, string $userId): bool
    {
        return (bool) Notification::query()
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => Date::now(),
            ]);
    }

    public function markAllAsRead(string $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => Date::now(),
            ]);
    }

    public function unreadCount(string $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    public function findForUser(string $notificationId, string $userId): ?array
    {
        $notification = Notification::query()
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        return $notification?->toArray();
    }

    public function deleteReadForUser(string $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', 1)
            ->delete();
    }

    public function getKpisForUser(string $userId): array
    {
        $countsByType = Notification::query()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->selectRaw('notification_type, COUNT(*) as aggregate')
            ->groupBy('notification_type')
            ->pluck('aggregate', 'notification_type');

        $kpis = array_merge(
            array_fill_keys(
                array_map(
                    static fn (NotificationKpiCategoryEnum $category) => $category->value,
                    NotificationKpiCategoryEnum::cases(),
                ),
                0,
            ),
            ['unread' => 0],
        );

        foreach ($countsByType as $typeValue => $count) {
            $count = (int) $count;
            $kpis['unread'] += $count;

            $category = NotificationTypeEnum::from((int) $typeValue)->kpiCategory();
            $kpis[$category->value] += $count;
        }

        return $kpis;
    }
}
