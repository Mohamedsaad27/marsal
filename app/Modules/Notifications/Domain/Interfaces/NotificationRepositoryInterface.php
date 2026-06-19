<?php

namespace App\Modules\Notifications\Domain\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    /**
     * Persist a new notification record and return it as a plain array.
     */
    public function create(array $data): array;

    /**
     * Paginated list of notifications for a given user, newest first.
     * Each page item is a Notification model (consumed by the Resource layer).
     */
    public function getForUser(string $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Mark a single notification as read (sets is_read=1 and read_at=now).
     * Returns true if the record was found and updated.
     */
    public function markAsRead(string $notificationId, string $userId): bool;

    /**
     * Mark every unread notification for the user as read.
     * Returns the number of rows updated.
     */
    public function markAllAsRead(string $userId): int;

    /**
     * Count unread notifications for the user.
     */
    public function unreadCount(string $userId): int;

    /**
     * Find a single notification by ID scoped to a user, returned as a plain array.
     * Returns null if not found or does not belong to the user.
     */
    public function findForUser(string $notificationId, string $userId): ?array;

    /**
     * Permanently delete all read notifications for the user.
     * Returns the number of rows deleted.
     */
    public function deleteReadForUser(string $userId): int;

    /**
     * Unread counts grouped by dashboard KPI category for the user.
     *
     * @return array{
     *     approvals: int,
     *     collections: int,
     *     shipments: int,
     *     unread: int
     * }
     */
    public function getKpisForUser(string $userId): array;
}
