<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;

class MarkSettlementPaidUseCase
{
    public function __construct(
        private SettlementRepositoryInterface $repository,
    ) {}

    public function execute(
        string $settlementId,
        string $paymentMethod,
        ?string $paymentReference,
        ?string $notes,
    ): Settlement {
        return $this->repository->markPaid(
            settlementId: $settlementId,
            paymentMethod: $paymentMethod,
            paymentReference: $paymentReference,
            notes: $notes,
        );
    }
}
