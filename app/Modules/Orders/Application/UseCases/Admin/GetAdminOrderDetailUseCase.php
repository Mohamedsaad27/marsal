<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;

class GetAdminOrderDetailUseCase
{
    public function __construct(
        private AdminOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $orderId): Order
    {
        $order = $this->repository->findWithRelations($orderId);

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        return $order;
    }
}
