<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Application\DTOs\OrderStatusChangePayload;
use App\Modules\Orders\Application\DTOs\UpdateAdminOrderStatusDTO;
use App\Modules\Orders\Application\Exceptions\OrderAgentRequiredException;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use App\Modules\Orders\Domain\Services\OrderStatusChangeService;
use App\Modules\Orders\Domain\Services\OrderStatusTransitionService;

class UpdateAdminOrderStatusUseCase
{
    public function __construct(
        private AdminOrderRepositoryInterface $repository,
        private OrderStatusTransitionService $transitions,
        private OrderStatusChangeService $statusChange,
    ) {}

    public function execute(UpdateAdminOrderStatusDTO $dto): array
    {
        $order = $this->repository->findWithRelations($dto->orderId);

        if ($order === null) {
            throw new OrderNotFoundException($dto->orderId);
        }

        $currentStatus = $order->status;

        $this->transitions->assertAdminCanTransition($currentStatus, $dto->requestedStatus);
        $this->assertAgentAssigned($order->delivery_agent_id, $dto->requestedStatus);

        return $this->statusChange->apply($order, new OrderStatusChangePayload(
            changedByUserId: $dto->adminUserId,
            deliveryAgentId: (string) $order->delivery_agent_id,
            requestedStatus: $dto->requestedStatus,
            collectedAmount: $dto->collectedAmount,
            collectionType: $dto->collectionType,
            newCodAmount: $dto->newCodAmount,
            postponedDate: $dto->postponedDate,
            notes: $dto->notes,
            notifySuperAdminsOnAgentStatusChange: false,
        ));
    }

    private function assertAgentAssigned(?string $deliveryAgentId, OrderStatusEnum $targetStatus): void
    {
        $requiresAgent = $targetStatus->requiresCollection()
            || $targetStatus === OrderStatusEnum::Postponed
            || $targetStatus === OrderStatusEnum::DeliveredPriceChanged;

        if ($requiresAgent && $deliveryAgentId === null) {
            throw new OrderAgentRequiredException;
        }
    }
}
