<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;

class DeleteReadNotificationsUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Permanently delete all read notifications for the user.
     *
     * @return int Number of rows deleted
     */
    public function execute(string $userId): int
    {
        return $this->repository->deleteReadForUser($userId);
    }
}
