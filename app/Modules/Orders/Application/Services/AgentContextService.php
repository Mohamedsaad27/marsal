<?php

namespace App\Modules\Orders\Application\Services;

use App\Modules\Orders\Application\Exceptions\AgentProfileNotFoundException;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;

class AgentContextService
{
    public function resolveDeliveryAgentId(string $userId): string
    {
        $agent = DeliveryAgent::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($agent === null) {
            throw new AgentProfileNotFoundException();
        }

        return $agent->delivery_agent_id;
    }

    public function resolveDeliveryAgent(string $userId): DeliveryAgent
    {
        $agent = DeliveryAgent::query()
            ->with('user')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($agent === null) {
            throw new AgentProfileNotFoundException();
        }

        return $agent;
    }
}
