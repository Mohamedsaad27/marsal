<?php

namespace App\Modules\Orders\Application\UseCases\Company;

use App\Modules\Orders\Domain\Interfaces\CompanyOrderRepositoryInterface;

class GetCompanyDashboardUseCase
{
    public function __construct(
        private CompanyOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $companyId): array
    {
        $stats = $this->repository->getDashboardStats($companyId);
        $recentOrders = $this->repository->getRecentOrders($companyId, 5);

        return [
            'stats'         => $stats,
            'recent_orders' => $recentOrders,
        ];
    }
}
