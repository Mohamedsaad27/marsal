<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Application\Services\AgentContextService;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;

class GetAgentOrderDetailUseCase
{
    public function __construct(
        private AgentContextService $agentContext,
        private AgentOrderRepositoryInterface $orders,
    ) {}

    public function execute(string $userId, string $orderId): Order
    {
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);
        $order = $this->orders->findForAgent($orderId, $deliveryAgentId);

        if ($order === null) {
            throw new OrderNotFoundException();
        }

        return $order;
    }
}
