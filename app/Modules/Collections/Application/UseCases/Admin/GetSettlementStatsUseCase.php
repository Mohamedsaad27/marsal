<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;

class GetSettlementStatsUseCase
{
    public function __construct(
        private SettlementRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return $this->repository->stats();
    }
}
