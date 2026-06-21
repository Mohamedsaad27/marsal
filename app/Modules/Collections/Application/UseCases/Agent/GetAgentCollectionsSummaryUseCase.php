<?php

namespace App\Modules\Collections\Application\UseCases\Agent;

use App\Modules\Collections\Domain\Interfaces\AgentCollectionRepositoryInterface;
use App\Modules\Orders\Application\Services\AgentContextService;

class GetAgentCollectionsSummaryUseCase
{
    public function __construct(
        private AgentContextService $agentContext,
        private AgentCollectionRepositoryInterface $collections,
    ) {}

    public function execute(string $userId): array
    {
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        return $this->collections->getSummaryForAgent($deliveryAgentId);
    }
}
