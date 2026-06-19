<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Application\Exceptions\NotificationNotFoundException;
use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;

class MarkNotificationReadUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Mark a single notification as read and return the updated notification as a plain array.
     * Throws NotificationNotFoundException if the record does not belong to the user.
     *
     * @return array The updated notification.
     */
    public function execute(string $notificationId, string $userId): array
    {
        $notification = $this->repository->findForUser($notificationId, $userId);

        if (! $notification) {
            throw new NotificationNotFoundException($notificationId);
        }

        $this->repository->markAsRead($notificationId, $userId);

        return $this->repository->findForUser($notificationId, $userId);
    }
}
