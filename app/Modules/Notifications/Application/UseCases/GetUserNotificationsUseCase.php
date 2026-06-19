<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetUserNotificationsUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Return a paginated list of notifications for the given user, newest first.
     */
    public function execute(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getForUser($userId, $perPage);
    }
}
