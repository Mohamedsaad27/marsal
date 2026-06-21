<?php

namespace App\Modules\Collections\Infrastructure\Persistence\Repositories;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Domain\Interfaces\AgentCollectionRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AgentCollectionRepository implements AgentCollectionRepositoryInterface
{
    private const LIST_RELATIONS = [
        'order.customerInfo',
    ];

    public function paginateForAgent(
        string $deliveryAgentId,
        bool $settled,
        int $perPage,
    ): LengthAwarePaginator {
        return Collection::query()
            ->select([
                'collection_id',
                'order_id',
                'delivery_agent_id',
                'collection_type',
                'collected_amount',
                'settlement_id',
                'collected_at',
            ])
            ->with(self::LIST_RELATIONS)
            ->where('delivery_agent_id', $deliveryAgentId)
            ->when(
                $settled,
                fn ($query) => $query->whereNotNull('settlement_id'),
                fn ($query) => $query->whereNull('settlement_id'),
            )
            ->orderByDesc('collected_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getSummaryForAgent(string $deliveryAgentId): array
    {
        $aggregates = DB::table('collections')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereNull('settlement_id')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as unsettled_count')
            ->selectRaw('COALESCE(SUM(collected_amount), 0) as total_unsettled')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN collection_type = ? THEN collected_amount ELSE 0 END), 0) as cod_total',
                [CollectionTypeEnum::Cod->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN collection_type = ? THEN collected_amount ELSE 0 END), 0) as shipping_fee_total',
                [CollectionTypeEnum::ShippingFee->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN collection_type = ? THEN collected_amount ELSE 0 END), 0) as partial_total',
                [CollectionTypeEnum::Partial->value],
            )
            ->first();

        $agentBalance = (float) DeliveryAgent::query()
            ->whereKey($deliveryAgentId)
            ->value('balance');

        $lastSettlementDate = DB::table('settlements')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->where('settlement_type', SettlementTypeEnum::Agent->value)
            ->where('settlement_status', SettlementStatusEnum::Paid->value)
            ->whereNull('deleted_at')
            ->orderByDesc('paid_at')
            ->value(DB::raw('DATE(COALESCE(paid_at, period_to))'));

        return [
            'total_unsettled' => round((float) ($aggregates->total_unsettled ?? 0), 2),
            'unsettled_count' => (int) ($aggregates->unsettled_count ?? 0),
            'breakdown' => [
                'cod' => round((float) ($aggregates->cod_total ?? 0), 2),
                'shipping_fee' => round((float) ($aggregates->shipping_fee_total ?? 0), 2),
                'partial' => round((float) ($aggregates->partial_total ?? 0), 2),
            ],
            'last_settlement_date' => $lastSettlementDate,
            'agent_balance' => round($agentBalance, 2),
        ];
    }
}
