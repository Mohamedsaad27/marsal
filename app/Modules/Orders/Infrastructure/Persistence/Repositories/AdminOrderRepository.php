<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Repositories;

use App\Modules\Orders\Application\DTOs\AdminOrderExportFilterDTO;
use App\Modules\Orders\Application\DTOs\AdminOrderFilterDTO;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Orders\Infrastructure\Database\Models\OrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class AdminOrderRepository implements AdminOrderRepositoryInterface
{
    private const EXPORT_RELATIONS = [
        'customerInfo',
        'financials',
        'address.governorate',
        'items',
        'shippingCompany',
        'deliveryAgent.user',
    ];

    private const LIST_RELATIONS = [
        'customerInfo',
        'financials',
        'address.governorate',
        'address.city',
        'shippingCompany.user',
        'deliveryAgent.user',
    ];

    private const DETAIL_RELATIONS = [
        'customerInfo',
        'financials',
        'address.governorate',
        'address.city',
        'items',
        'schedule',
        'approvals',
        'statusHistory.changedByUser',
        'proofs',
        'shippingCompany.user',
        'deliveryAgent.user',
    ];

    public function stats(): array
    {
        $base = Order::query()->whereNull('deleted_at');

        $total = (clone $base)->count();
        $returned = (clone $base)->whereExists(fn ($q) => $q
            ->select(DB::raw(1))
            ->from('returns')
            ->whereColumn('returns.order_id', 'orders.order_id')
        )->count();

        $statuses = array_map(
            static function (OrderStatusEnum $status) use ($base) {
                return [
                    'id'       => $status->value,
                    'label_ar' => $status->labelAr(),
                    'count'    => (clone $base)->where('status', $status->value)->count(),
                ];
            },
            OrderStatusEnum::cases(),
        );

        return [
            'total'    => $total,
            'returned' => $returned,
            'statuses' => $statuses,
        ];
    }

    public function paginate(AdminOrderFilterDTO $filter): LengthAwarePaginator
    {
        $query = $this->baseOrderQuery()
            ->with(self::LIST_RELATIONS)
            ->orderByDesc('created_at');

        $this->applyListFilters($query, $filter);

        return $query->paginate($filter->perPage);
    }

    public function lazyForExport(AdminOrderExportFilterDTO $filter): LazyCollection
    {
        $query = $this->baseOrderQuery()
            ->with(self::EXPORT_RELATIONS)
            ->orderBy('created_at');

        $this->applyExportFilters($query, $filter);

        return $query->lazy(500);
    }

    private function baseOrderQuery(): Builder
    {
        return Order::query()->whereNull('deleted_at');
    }

    private function applyListFilters(Builder $query, AdminOrderFilterDTO $filter): void
    {
        if ($filter->status !== null && $filter->status !== 'all') {
            $this->applyStatusFilter($query, $filter->status);
        }

        if ($filter->companyId !== null) {
            $query->where('shipping_company_id', $filter->companyId);
        }

        if ($filter->agentId !== null) {
            $query->where('delivery_agent_id', $filter->agentId);
        }

        if ($filter->governorateId !== null) {
            $query->whereHas('address', fn ($q) => $q->where('governorate_id', $filter->governorateId));
        }

        if ($filter->dateFrom !== null) {
            $query->whereDate('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->whereDate('created_at', '<=', $filter->dateTo);
        }

        if ($filter->search !== null && trim($filter->search) !== '') {
            $term = '%' . trim($filter->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('reference_code', 'like', $term)
                  ->orWhere('reference_no', 'like', $term)
                  ->orWhereHas('customerInfo', fn ($c) => $c
                      ->where('customer_name', 'like', $term)
                      ->orWhere('customer_phone', 'like', $term));
            });
        }
    }

    private function applyExportFilters(Builder $query, AdminOrderExportFilterDTO $filter): void
    {
        if ($filter->shippingCompanyId !== null) {
            $query->where('shipping_company_id', $filter->shippingCompanyId);
        }

        if ($filter->deliveryAgentId !== null) {
            $query->where('delivery_agent_id', $filter->deliveryAgentId);
        }

        if ($filter->status !== null && $filter->status !== '') {
            $this->applyExportStatusFilter($query, $filter->status);
        }

        if ($filter->dateFrom !== null) {
            $query->whereDate('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->whereDate('created_at', '<=', $filter->dateTo);
        }
    }

    private function applyExportStatusFilter(Builder $query, string $status): void
    {
        if (! str_contains($status, ',')) {
            $this->applyStatusFilter($query, $status);

            return;
        }

        $statusIds = [];

        foreach (explode(',', $status) as $part) {
            $part = trim($part);

            if ($part === '' || ! ctype_digit($part)) {
                continue;
            }

            $orderStatus = OrderStatusEnum::tryFrom((int) $part);

            if ($orderStatus !== null) {
                $statusIds[] = $orderStatus->value;
            }
        }

        if ($statusIds !== []) {
            $query->whereIn('status', array_values(array_unique($statusIds)));
        }
    }

    public function findWithRelations(string $orderId): ?Order
    {
        return Order::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('order_id', $orderId)
            ->first();
    }

    public function findById(string $orderId): ?Order
    {
        return Order::query()->find($orderId);
    }

    public function assignAgent(string $orderId, string $agentId, string $adminUserId): Order
    {
        $order = Order::query()->findOrFail($orderId);

        $fromStatus = $order->status instanceof OrderStatusEnum
            ? $order->status->value
            : (int) $order->status;

        $order->update([
            'delivery_agent_id' => $agentId,
            'assigned_at'       => Carbon::now(),
            'status'            => OrderStatusEnum::Assigned->value,
        ]);

        OrderStatusHistory::create([
            'order_status_history_id' => (string) Str::uuid(),
            'order_id'                => $orderId,
            'from_status_id'          => $fromStatus,
            'to_status_id'            => OrderStatusEnum::Assigned->value,
            'changed_by'              => $adminUserId,
            'notes'                   => null,
        ]);

        return $order->fresh(self::DETAIL_RELATIONS);
    }

    public function softDelete(Order $order): void
    {
        $order->delete();
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'returned') {
            $query->whereExists(fn ($q) => $q
                ->select(DB::raw(1))
                ->from('returns')
                ->whereColumn('returns.order_id', 'orders.order_id'));

            return;
        }

        if (ctype_digit($status)) {
            $orderStatus = OrderStatusEnum::tryFrom((int) $status);

            if ($orderStatus !== null) {
                $query->where('status', $orderStatus->value);
            }
        }
    }
}
