<?php

namespace App\Modules\Orders\Application\UseCases\Company;

use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Orders\Domain\Interfaces\CompanyOrderRepositoryInterface;

class GetCompanyProfileUseCase
{
    public function __construct(
        private CompanyOrderRepositoryInterface $orderRepository,
        private SettlementRepositoryInterface $settlementRepository,
    ) {}

    public function execute(string $companyId, array $companyData): array
    {
        $stats = $this->orderRepository->getOrderStats($companyId);
        $lastSettlement = $this->settlementRepository->getLastPaidForCompany($companyId);

        return array_merge($companyData, [
            'stats'           => $stats,
            'last_settlement' => $lastSettlement,
        ]);
    }
}
