<?php

namespace App\Modules\Orders\Domain\Interfaces;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AgentOrderRepositoryInterface
{
    public function paginateForAgent(
        string $deliveryAgentId,
        ?string $statusFilter,
        ?string $search,
        int $perPage,
    ): LengthAwarePaginator;

    public function findForAgent(string $orderId, string $deliveryAgentId): ?Order;

    /** @return Collection<int, Order> */
    public function getUpcomingForAgent(string $deliveryAgentId, int $limit = 5): Collection;

    public function getTodayCollectedAmount(string $deliveryAgentId): float;

    public function countActiveOrders(string $deliveryAgentId): int;

    public function countDeliveredToday(string $deliveryAgentId): int;

    public function getWeeklyDeliveryRatePercent(string $deliveryAgentId): int;

    /**
     * @return Collection<int, Order>
     */
    public function listPostponedForAgent(
        string $deliveryAgentId,
        ?string $date,
        ?string $month,
    ): Collection;

    /**
     * @return array{month: string, total_postponed: int, dates: array<string, int>}
     */
    public function getPostponedCalendarForAgent(string $deliveryAgentId, string $month): array;
}
