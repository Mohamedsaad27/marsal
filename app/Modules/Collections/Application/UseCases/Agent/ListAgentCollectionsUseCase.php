<?php

namespace App\Modules\Collections\Application\UseCases\Agent;

use App\Modules\Collections\Domain\Interfaces\AgentCollectionRepositoryInterface;
use App\Modules\Orders\Application\Services\AgentContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAgentCollectionsUseCase
{
    public function __construct(
        private AgentContextService $agentContext,
        private AgentCollectionRepositoryInterface $collections,
    ) {}

    public function execute(string $userId, bool $settled, int $perPage): LengthAwarePaginator
    {
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        return $this->collections->paginateForAgent(
            deliveryAgentId: $deliveryAgentId,
            settled: $settled,
            perPage: $perPage,
        );
    }
}
