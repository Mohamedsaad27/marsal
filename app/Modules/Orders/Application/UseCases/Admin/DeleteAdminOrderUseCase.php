<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Application\Exceptions\OrderDeletionBlockedException;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;

class DeleteAdminOrderUseCase
{
    public function __construct(
        private readonly AdminOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $orderId): void
    {
        $order = $this->repository->findById($orderId);

        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $status = $order->status instanceof OrderStatusEnum
            ? $order->status
            : OrderStatusEnum::tryFrom((int) $order->status);

        if ($status !== OrderStatusEnum::Pending) {
            throw new OrderDeletionBlockedException();
        }

        $this->repository->softDelete($order);
    }
}
