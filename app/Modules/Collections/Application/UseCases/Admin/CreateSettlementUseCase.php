<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Notifications\Domain\Events\SettlementCreated;

class CreateSettlementUseCase
{
    public function __construct(
        private SettlementRepositoryInterface $repository,
    ) {}

    public function execute(CreateSettlementDTO $dto): Settlement
    {
        $settlement = $this->repository->createFromEligibleCollections($dto);

        $settlement->load(['deliveryAgent.user', 'shippingCompany']);

        event(new SettlementCreated(
            settlementId: $settlement->settlement_id,
            entityLabel: $this->resolveEntityLabel($settlement),
            paymentDirection: $settlement->paymentDirection(),
            payableAmount: number_format($settlement->payableAmount(), 2, '.', ''),
            settlementType: $settlement->settlement_type,
            agentUserId: $settlement->deliveryAgent?->user?->user_id,
            companyUserId: $settlement->shippingCompany?->user?->user_id,
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
