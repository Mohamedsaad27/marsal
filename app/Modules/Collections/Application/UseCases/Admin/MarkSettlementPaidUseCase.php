<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Notifications\Domain\Events\SettlementPaid;

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
        $settlement = $this->repository->markPaid(
            settlementId: $settlementId,
            paymentMethod: $paymentMethod,
            paymentReference: $paymentReference,
            notes: $notes,
        );

        $settlement->load(['deliveryAgent.user', 'shippingCompany']);

        event(new SettlementPaid(
            settlementId: $settlement->settlement_id,
            entityLabel: $this->resolveEntityLabel($settlement),
            netAmount: number_format((float) $settlement->net_amount, 2, '.', ''),
        ));

        return $settlement;
    }

    private function resolveEntityLabel(Settlement $settlement): string
    {
        if ($settlement->settlement_type === SettlementTypeEnum::Agent) {
            return $settlement->deliveryAgent?->user?->name ?? 'مندوب';
        }

        return $settlement->shippingCompany?->company_name ?? 'شركة شحن';
    }
}
