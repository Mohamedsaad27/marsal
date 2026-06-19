<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;

class GetNotificationKpisUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Unread notification counts grouped by dashboard KPI category.
     *
     * @return array{
     *     approvals: int,
     *     collections: int,
     *     shipments: int,
     *     unread: int
     * }
     */
    public function execute(string $userId): array
    {
        return $this->repository->getKpisForUser($userId);
    }
}
