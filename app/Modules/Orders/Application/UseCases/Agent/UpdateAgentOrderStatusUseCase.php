<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\DTOs\OrderStatusChangePayload;
use App\Modules\Orders\Application\DTOs\UpdateAgentOrderStatusDTO;
use App\Modules\Orders\Application\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use App\Modules\Orders\Domain\Services\OrderStatusChangeService;
use App\Modules\Orders\Domain\Services\OrderStatusTransitionService;

class UpdateAgentOrderStatusUseCase
{
    public function __construct(
        private AgentOrderRepositoryInterface $orders,
        private OrderStatusTransitionService $transitions,
        private OrderStatusChangeService $statusChange,
    ) {}

    public function execute(UpdateAgentOrderStatusDTO $dto): array
    {
        $order = $this->orders->findForAgent($dto->orderId, $dto->deliveryAgentId);

        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $currentStatus = $order->status;

        try {
            $this->transitions->assertCanTransition($currentStatus, $dto->requestedStatus);
        } catch (\InvalidArgumentException) {
            throw new InvalidOrderStatusTransitionException();
        }

        return $this->statusChange->apply($order, new OrderStatusChangePayload(
            changedByUserId: $dto->userId,
            deliveryAgentId: $dto->deliveryAgentId,
            requestedStatus: $dto->requestedStatus,
            collectedAmount: $dto->collectedAmount,
            collectionType: $dto->collectionType,
            newCodAmount: $dto->newCodAmount,
            postponedDate: $dto->postponedDate,
            notes: $dto->notes,
        ));
    }
}
