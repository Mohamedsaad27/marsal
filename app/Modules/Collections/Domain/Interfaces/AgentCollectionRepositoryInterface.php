<?php

namespace App\Modules\Collections\Domain\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AgentCollectionRepositoryInterface
{
    public function paginateForAgent(
        string $deliveryAgentId,
        bool $settled,
        int $perPage,
    ): LengthAwarePaginator;

    /**
     * @return array{
     *     total_agent_net_due: float,
     *     agent_to_system_amount: float,
     *     system_to_agent_amount: float,
     *     unsettled_count: int,
     *     breakdown: array{cod: float, shipping_fee: float, partial: float},
     *     last_settlement_date: ?string,
     *     agent_balance: float,
     *     agent_balance_direction: string,
     *     agent_balance_amount: float
     * }
     */
    public function getSummaryForAgent(string $deliveryAgentId): array;
}
