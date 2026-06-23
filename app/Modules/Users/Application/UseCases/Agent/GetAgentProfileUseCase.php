<?php

namespace App\Modules\Users\Application\UseCases\Agent;

use App\Modules\Users\Application\Exceptions\AgentProfileNotFoundException;
use App\Modules\Users\Domain\Interfaces\DeliveryAgentRepositoryInterface;

class GetAgentProfileUseCase
{
    public function __construct(
        private DeliveryAgentRepositoryInterface $agents,
    ) {}

    public function execute(string $userId): array
    {
        $agent = $this->agents->findByUserIdForProfile($userId);

        if ($agent === null || $agent->user === null) {
            throw new AgentProfileNotFoundException();
        }

        return [
            'user' => $agent->user,
            'agent' => $agent,
            'stats' => [
                'total_delivered' => $this->agents->countDeliveredOrders($agent->delivery_agent_id),
                'average_rating' => null,
                'active_since' => $agent->created_at?->toDateString(),
            ],
        ];
    }
}
