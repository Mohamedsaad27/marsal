<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;

class GetUnreadCountUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Return the count of unread notifications for the given user.
     * Used to populate the notification badge on mobile and web.
     */
    public function execute(string $userId): int
    {
        return $this->repository->unreadCount($userId);
    }
}
