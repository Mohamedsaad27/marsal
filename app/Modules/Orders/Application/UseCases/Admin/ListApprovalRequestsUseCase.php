<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Domain\Interfaces\ApprovalRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListApprovalRequestsUseCase
{
    public function __construct(
        private ApprovalRequestRepositoryInterface $repository,
    ) {}

    public function execute(
        ?int $status,
        ?int $type,
        ?string $agentId,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->repository->paginate($status, $type, $agentId, $perPage);
    }
}
