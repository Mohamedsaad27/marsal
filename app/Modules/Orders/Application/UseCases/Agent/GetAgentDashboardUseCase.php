<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\Services\AgentContextService;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;

class GetAgentDashboardUseCase
{
    public function __construct(
        private AgentContextService $agentContext,
        private AgentOrderRepositoryInterface $orders,
    ) {}

    public function execute(string $userId): array
    {
        $user = User::query()->findOrFail($userId);
        $deliveryAgentId = $this->agentContext->resolveDeliveryAgentId($userId);

        $activeCount = $this->orders->countActiveOrders($deliveryAgentId);
        $deliveredToday = $this->orders->countDeliveredToday($deliveryAgentId);

        return [
            'agent' => [
                'name' => $user->name,
                'fcm_token_registered' => ! empty($user->fcm_token),
            ],
            'today' => [
                'orders_count' => $activeCount + $deliveredToday,
                'collected_amount' => $this->orders->getTodayCollectedAmount($deliveryAgentId),
                'delivered_count' => $deliveredToday,
                'pending_count' => $activeCount,
            ],
            'performance' => [
                'delivery_rate_percent' => $this->orders->getWeeklyDeliveryRatePercent($deliveryAgentId),
                'week_label' => 'هذا الأسبوع',
            ],
            'upcoming_orders' => $this->orders->getUpcomingForAgent($deliveryAgentId, 5),
        ];
    }
}
