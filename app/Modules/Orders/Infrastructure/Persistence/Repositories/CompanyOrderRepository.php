<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Repositories;

use App\Modules\Collections\Infrastructure\Database\Models\Collection as CollectionModel;
use App\Modules\Orders\Application\DTOs\CompanyOrderFilterDTO;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\CompanyOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CompanyOrderRepository implements CompanyOrderRepositoryInterface
{
    private const LIST_RELATIONS = [
        'customerInfo',
        'financials',
        'address.governorate',
        'address.city',
    ];

    private const DETAIL_RELATIONS = [
        'customerInfo',
        'financials',
        'address.governorate',
        'address.city',
        'items',
        'schedule',
        'statusHistory.changedByUser',
        'proofs',
    ];

    private const IN_DELIVERY_STATUSES = [
        OrderStatusEnum::Assigned,
        OrderStatusEnum::OutForDelivery,
        OrderStatusEnum::AwaitingApproval,
    ];

    private const DELIVERED_STATUSES = [
        OrderStatusEnum::Delivered,
        OrderStatusEnum::DeliveredPriceChanged,
        OrderStatusEnum::PartialDelivery,
        OrderStatusEnum::RefusedPaidShipping,
    ];

    public function paginate(CompanyOrderFilterDTO $filter, string $companyId): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(self::LIST_RELATIONS)
            ->where('shipping_company_id', $companyId)
            ->orderByDesc('created_at');

        if ($filter->status !== null && $filter->status !== 'all') {
            $this->applyStatusFilter($query, $filter->status);
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

        return $query->paginate($filter->perPage);
    }

    public function findForCompany(string $orderId, string $companyId): ?Order
    {
        return Order::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('order_id', $orderId)
            ->where('shipping_company_id', $companyId)
            ->first();
    }

    public function getDashboardStats(string $companyId): array
    {
        $base = Order::query()
            ->where('shipping_company_id', $companyId)
            ->whereNull('deleted_at');

        $total = (clone $base)->count();

        $inDeliveryCount = (clone $base)
            ->whereIn('status', array_map(fn ($s) => $s->value, self::IN_DELIVERY_STATUSES))
            ->count();

        $collectedToday = (float) CollectionModel::query()
            ->where('shipping_company_id', $companyId)
            ->whereDate('collected_at', Carbon::today())
            ->sum('net_due');

        $deliveryRatePercent = $this->calcDeliveryRate($companyId);

        return [
            'total_orders'          => $total,
            'in_delivery_count'     => $inDeliveryCount,
            'collected_today'       => round($collectedToday, 2),
            'delivery_rate_percent' => $deliveryRatePercent,
        ];
    }

    public function getRecentOrders(string $companyId, int $limit = 5): Collection
    {
        return Order::query()
            ->with(self::LIST_RELATIONS)
            ->where('shipping_company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getOrderStats(string $companyId): array
    {
        $total = Order::query()
            ->where('shipping_company_id', $companyId)
            ->whereNull('deleted_at')
            ->count();

        return [
            'total_orders'          => $total,
            'delivery_rate_percent' => $this->calcDeliveryRate($companyId),
        ];
    }

    public function getWalletAggregates(string $companyId): array
    {
        $base = CollectionModel::query()->where('shipping_company_id', $companyId);

        $totalCollected = (float) (clone $base)->sum('collected_amount');
        $totalCommissions = (float) (clone $base)->sum('commission_amount');
        $totalNetDue = (float) (clone $base)->sum('net_due');

        $pendingQuery = (clone $base)
            ->whereNotNull('cash_received_at')
            ->whereNull('settlement_id');

        $pendingSettlementAmount = (float) (clone $pendingQuery)->sum('net_due');
        $pendingCollectionCount = (int) (clone $pendingQuery)->count();

        return [
            'total_collected'           => round($totalCollected, 2),
            'total_commissions'         => round($totalCommissions, 2),
            'total_net_due'             => round($totalNetDue, 2),
            'pending_settlement_amount' => round($pendingSettlementAmount, 2),
            'pending_collection_count'  => $pendingCollectionCount,
        ];
    }

    private function calcDeliveryRate(string $companyId): int
    {
        $terminalBase = Order::query()
            ->where('shipping_company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereIn('status', array_map(
                fn ($s) => $s->value,
                array_filter(OrderStatusEnum::cases(), fn ($s) => $s->isTerminal()),
            ));

        $total = (clone $terminalBase)->count();

        if ($total === 0) {
            return 0;
        }

        $delivered = (clone $terminalBase)
            ->whereIn('status', array_map(fn ($s) => $s->value, self::DELIVERED_STATUSES))
            ->count();

        return (int) round(($delivered / $total) * 100);
    }

    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'pending'     => $query->where('status', OrderStatusEnum::Pending->value),
            'in_delivery' => $query->whereIn('status', array_map(
                fn ($s) => $s->value, self::IN_DELIVERY_STATUSES
            )),
            'delivered'   => $query->whereIn('status', array_map(
                fn ($s) => $s->value, self::DELIVERED_STATUSES
            )),
            'returned'    => $query->whereIn('status', [
                OrderStatusEnum::RefusedNoPayment->value,
                OrderStatusEnum::CustomerCancelled->value,
            ]),
            default       => null,
        };
    }
}
