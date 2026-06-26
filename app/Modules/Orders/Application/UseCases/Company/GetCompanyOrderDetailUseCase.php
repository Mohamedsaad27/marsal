<?php

namespace App\Modules\Orders\Application\UseCases\Company;

use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Interfaces\CompanyOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;

class GetCompanyOrderDetailUseCase
{
    public function __construct(
        private CompanyOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $orderId, string $companyId): Order
    {
        $order = $this->repository->findForCompany($orderId, $companyId);

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        return $order;
    }
}
