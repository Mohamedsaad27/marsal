<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\Services\AgentContextService;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;

class GetScheduleCalendarUseCase
{
    public function __construct(
        private AgentContextService $agentContext,
        private AgentOrderRepositoryInterface $orders,
    ) {}

    /**
     * @return array{month: string, total_postponed: int, dates: array<string, int>}
     */
    public function execute(string $userId, string $month): array
    {
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        return $this->orders->getPostponedCalendarForAgent($deliveryAgentId, $month);
    }
}
