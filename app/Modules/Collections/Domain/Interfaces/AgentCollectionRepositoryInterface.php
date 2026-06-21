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
     *     total_unsettled: float,
     *     unsettled_count: int,
     *     breakdown: array{cod: float, shipping_fee: float, partial: float},
     *     last_settlement_date: ?string,
     *     agent_balance: float
     * }
     */
    public function getSummaryForAgent(string $deliveryAgentId): array;
}
