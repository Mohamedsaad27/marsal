<?php

namespace App\Modules\Collections\Infrastructure\Persistence\Repositories;

use App\Modules\Collections\Application\DTOs\AdminCollectionFilterDTO;
use App\Modules\Collections\Application\Exceptions\CollectionAlreadyReceivedException;
use App\Modules\Collections\Application\Exceptions\CollectionNotFoundException;
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
    ];

    public function stats(): array
    {
        $base = Collection::query();

        $aggregates = (clone $base)
            ->selectRaw('COALESCE(SUM(collected_amount), 0) as total_collected')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commissions')
            ->selectRaw('COALESCE(SUM(net_due), 0) as net_due_to_companies')
            ->first();

        $pendingCashCount = (clone $base)
            ->whereNull('cash_received_at')
            ->whereNull('settlement_id')
            ->count();

        return [
            'total_collected' => number_format((float) ($aggregates->total_collected ?? 0), 2, '.', ''),
            'total_commissions' => number_format((float) ($aggregates->total_commissions ?? 0), 2, '.', ''),
            'net_due_to_companies' => number_format((float) ($aggregates->net_due_to_companies ?? 0), 2, '.', ''),
            'pending_cash_count' => $pendingCashCount,
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
            throw new CollectionNotFoundException();
        }

        return $collection;
    }

    public function markCashReceived(string $collectionId, string $receivedBy): Collection
    {
        $collection = $this->findOrFail($collectionId);

        if ($collection->cash_received_at !== null) {
            throw new CollectionAlreadyReceivedException();
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

        match ($filter->status) {
            'pending_cash' => $query->whereNull('cash_received_at')->whereNull('settlement_id'),
            'unsettled' => $query->whereNotNull('cash_received_at')->whereNull('settlement_id'),
            'settled' => $query->whereNotNull('settlement_id'),
            default => null,
        };

        if ($filter->search !== null && $filter->search !== '') {
            $search = '%' . $filter->search . '%';

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
}
