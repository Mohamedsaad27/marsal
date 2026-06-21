<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\Services\AgentContextService;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAgentOrdersUseCase
{
    public function __construct(
        private AgentContextService $agentContext,
        private AgentOrderRepositoryInterface $orders,
    ) {}

    public function execute(
        string $userId,
        ?string $statusFilter,
        ?string $search,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        return $this->orders->paginateForAgent(
            deliveryAgentId: $deliveryAgentId,
            statusFilter: $statusFilter,
            search: $search,
            perPage: min($perPage, 100),
        );
    }
}
