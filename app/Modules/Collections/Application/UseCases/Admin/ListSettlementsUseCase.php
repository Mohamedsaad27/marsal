<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Application\DTOs\SettlementFilterDTO;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSettlementsUseCase
{
    public function __construct(
        private SettlementRepositoryInterface $repository,
    ) {}

    public function execute(SettlementFilterDTO $filter): LengthAwarePaginator
    {
        return $this->repository->paginate($filter);
    }
}
