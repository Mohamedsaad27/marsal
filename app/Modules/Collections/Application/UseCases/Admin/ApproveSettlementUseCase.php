<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;

class ApproveSettlementUseCase
{
    public function __construct(
        private SettlementRepositoryInterface $repository,
    ) {}

    public function execute(string $settlementId): Settlement
    {
        return $this->repository->approve($settlementId);
    }
}
