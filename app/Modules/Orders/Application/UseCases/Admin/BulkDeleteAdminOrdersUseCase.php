<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

class BulkDeleteAdminOrdersUseCase
{
    public function __construct(
        private readonly DeleteAdminOrderUseCase $deleteAdminOrderUseCase,
    ) {}

    public function execute(array $orderIds): void
    {
        foreach (array_values(array_unique($orderIds)) as $orderId) {
            $this->deleteAdminOrderUseCase->execute($orderId);
        }
    }
}
