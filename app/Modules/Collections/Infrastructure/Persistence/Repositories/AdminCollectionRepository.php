<?php

namespace App\Modules\Collections\Infrastructure\Persistence\Repositories;

use App\Modules\Collections\Application\DTOs\AdminCollectionFilterDTO;
use App\Modules\Collections\Application\Exceptions\CollectionAlreadyReceivedException;
use App\Modules\Collections\Application\Exceptions\CollectionNotFoundException;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Interfaces\AdminCollectionRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminCollectionRepository implements AdminCollectionRepositoryInterface
{
    private const LIST_RELATIONS = [
        'order',
        'deliveryAgent.user',
        'shippingCompany.user',
        'cashReceivedBy',
        'agentSettlementItem.settlement',
        'companySettlementItem.settlement',
    ];

    public function stats(): array
    {
        $base = Collection::query();

        $aggregates = (clone $base)
            ->selectRaw('COALESCE(SUM(collected_amount), 0) as total_collected')
            ->selectRaw('COALESCE(SUM(agent_commission_amount), 0) as total_agent_commission_amount')
            ->selectRaw('COALESCE(SUM(agent_net_due), 0) as total_agent_net_due')
            ->selectRaw('COALESCE(SUM(system_commission_amount), 0) as total_system_commission_amount')
            ->selectRaw('COALESCE(SUM(company_net_due), 0) as total_company_net_due')
            ->selectRaw('COALESCE(SUM(CASE WHEN agent_net_due > 0 THEN agent_net_due ELSE 0 END), 0) as agent_to_system_amount')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN agent_net_due < 0 THEN agent_net_due ELSE 0 END), 0)) as system_to_agent_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN company_net_due > 0 THEN company_net_due ELSE 0 END), 0) as system_to_company_amount')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN company_net_due < 0 THEN company_net_due ELSE 0 END), 0)) as company_to_system_amount')
            ->first();

        $pendingCashCount = (clone $base)
            ->whereNull('cash_received_at')
            ->where('agent_net_due', '>', 0)
            ->count();
        $systemNetProfit = (float) ($aggregates->total_collected ?? 0)
            - (float) ($aggregates->total_agent_commission_amount ?? 0)
            - (float) ($aggregates->total_company_net_due ?? 0);

        return [
            'total_collected' => number_format((float) ($aggregates->total_collected ?? 0), 2, '.', ''),
            'total_agent_commission_amount' => $this->money($aggregates->total_agent_commission_amount),
            'total_agent_net_due' => $this->money($aggregates->total_agent_net_due),
            'total_system_commission_amount' => $this->money($aggregates->total_system_commission_amount),
            'total_company_net_due' => $this->money($aggregates->total_company_net_due),
            'agent_to_system_amount' => $this->money($aggregates->agent_to_system_amount),
            'system_to_agent_amount' => $this->money($aggregates->system_to_agent_amount),
            'system_to_company_amount' => $this->money($aggregates->system_to_company_amount),
            'company_to_system_amount' => $this->money($aggregates->company_to_system_amount),
            'pending_cash_count' => $pendingCashCount,
            'system_net_profit' => $this->money($systemNetProfit),
        ];
    }

    public function paginate(AdminCollectionFilterDTO $filter): LengthAwarePaginator
    {
        $query = Collection::query()
            ->with(self::LIST_RELATIONS)
            ->orderByDesc('collected_at')
            ->orderByDesc('created_at');

        $this->applyFilters($query, $filter);

        return $query->paginate($filter->perPage);
    }

    public function findOrFail(string $collectionId): Collection
    {
        $collection = Collection::query()
            ->with(self::LIST_RELATIONS)
            ->where('collection_id', $collectionId)
            ->first();

        if ($collection === null) {
            throw new CollectionNotFoundException;
        }

        return $collection;
    }

    public function markCashReceived(string $collectionId, string $receivedBy): Collection
    {
        $collection = $this->findOrFail($collectionId);

        if ($collection->cash_received_at !== null) {
            throw new CollectionAlreadyReceivedException;
        }

        $collection->update([
            'cash_received_at' => Carbon::now(),
            'cash_received_by' => $receivedBy,
        ]);

        return $collection->fresh(self::LIST_RELATIONS);
    }

    private function applyFilters(Builder $query, AdminCollectionFilterDTO $filter): void
    {
        if ($filter->collectionType !== null) {
            $query->where('collection_type', $filter->collectionType);
        }

        if ($filter->agentId !== null) {
            $query->where('delivery_agent_id', $filter->agentId);
        }

        if ($filter->companyId !== null) {
            $query->where('shipping_company_id', $filter->companyId);
        }

        if ($filter->dateFrom !== null) {
            $query->whereDate('collected_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->whereDate('collected_at', '<=', $filter->dateTo);
        }

        $this->applyStatusFilter($query, $filter->status);

        if ($filter->search !== null && $filter->search !== '') {
            $search = '%'.$filter->search.'%';

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('reference_code', 'like', $search))
                    ->orWhereHas('deliveryAgent.user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $search))
                    ->orWhereHas('shippingCompany', fn (Builder $companyQuery) => $companyQuery
                        ->where('company_name', 'like', $search)
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $search)));
            });
        }
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status === 'pending_cash') {
            $query->whereNull('cash_received_at')->where('agent_net_due', '>', 0);

            return;
        }

        if ($status === 'settled') {
            $query
                ->whereHas('agentSettlementItem.settlement', fn (Builder $settlement) => $settlement
                    ->where('settlement_status', SettlementStatusEnum::Paid->value))
                ->whereHas('companySettlementItem.settlement', fn (Builder $settlement) => $settlement
                    ->where('settlement_status', SettlementStatusEnum::Paid->value));

            return;
        }

        if ($status === 'unsettled') {
            $query
                ->where(fn (Builder $eligible) => $eligible
                    ->where('agent_net_due', '<=', 0)
                    ->orWhereNotNull('cash_received_at'))
                ->where(function (Builder $unsettled): void {
                    $unsettled
                        ->whereDoesntHave('agentSettlementItem.settlement', fn (Builder $settlement) => $settlement
                            ->where('settlement_status', SettlementStatusEnum::Paid->value))
                        ->orWhereDoesntHave('companySettlementItem.settlement', fn (Builder $settlement) => $settlement
                            ->where('settlement_status', SettlementStatusEnum::Paid->value));
                });
        }
    }

    private function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
