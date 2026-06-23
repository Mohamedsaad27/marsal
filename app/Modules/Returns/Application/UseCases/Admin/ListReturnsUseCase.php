<?php

namespace App\Modules\Returns\Application\UseCases\Admin;

use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListReturnsUseCase
{
    public function __construct(
        private ReturnRepositoryInterface $repository,
    ) {}

    public function execute(
        ?int $status,
        ?string $companyId,
        ?string $agentId,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->repository->paginate($status, $companyId, $agentId, $perPage);
    }
}
