<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Domain\Interfaces\ApprovalRequestRepositoryInterface;

class GetApprovalStatsUseCase
{
    public function __construct(
        private ApprovalRequestRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return $this->repository->stats();
    }
}
