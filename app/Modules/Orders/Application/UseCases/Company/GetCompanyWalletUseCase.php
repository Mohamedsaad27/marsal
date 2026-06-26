<?php

namespace App\Modules\Orders\Application\UseCases\Company;

use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Orders\Domain\Interfaces\CompanyOrderRepositoryInterface;

class GetCompanyWalletUseCase
{
    public function __construct(
        private CompanyOrderRepositoryInterface $orderRepository,
        private SettlementRepositoryInterface $settlementRepository,
    ) {}

    public function execute(string $companyId, float $balance): array
    {
        $aggregates = $this->orderRepository->getWalletAggregates($companyId);
        $lastSettlement = $this->settlementRepository->getLastPaidForCompany($companyId);

        return [
            'balance'                   => $balance,
            'total_collected'           => $aggregates['total_collected'],
            'total_commissions'         => $aggregates['total_commissions'],
            'total_net_due'             => $aggregates['total_net_due'],
            'pending_settlement_amount' => $aggregates['pending_settlement_amount'],
            'pending_collection_count'  => $aggregates['pending_collection_count'],
            'last_settlement'           => $lastSettlement,
        ];
    }
}
