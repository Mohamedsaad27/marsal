<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;

class MarkAllNotificationsReadUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Mark every unread notification for the user as read.
     *
     * @return int Number of rows updated
     */
    public function execute(string $userId): int
    {
        return $this->repository->markAllAsRead($userId);
    }
}
