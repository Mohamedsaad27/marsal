<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\Services\AgentContextService;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use Illuminate\Support\Collection;

class ListPostponedOrdersUseCase
{
    public function __construct(
        private AgentContextService $agentContext,
        private AgentOrderRepositoryInterface $orders,
    ) {}

    public function execute(string $userId, ?string $date, ?string $month): Collection
    {
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        return $this->orders->listPostponedForAgent(
            deliveryAgentId: $deliveryAgentId,
            date: $date,
            month: $month,
        );
    }
}
