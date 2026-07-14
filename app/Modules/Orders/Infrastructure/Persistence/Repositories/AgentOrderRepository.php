<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Repositories;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgentOrderRepository implements AgentOrderRepositoryInterface
{
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
        'shippingCompany',
    ];

    private const LIST_RELATIONS = [
        'customerInfo',
        'financials',
        'address.governorate',
        'address.city',
        'schedule',
    ];

    public function paginateForAgent(
        string $deliveryAgentId,
        ?string $statusFilter,
        ?string $search,
        int $perPage,
    ): LengthAwarePaginator {
        $query = Order::query()
            ->with(self::LIST_RELATIONS)
            ->where('delivery_agent_id', $deliveryAgentId)
            ->orderByDesc('assigned_at')
            ->orderByDesc('created_at');

        $this->applyStatusFilter($query, $statusFilter);
        $this->applySearch($query, $search);

        return $query->paginate($perPage);
    }

    public function findForAgent(string $orderId, string $deliveryAgentId): ?Order
    {
        return Order::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('order_id', $orderId)
            ->where('delivery_agent_id', $deliveryAgentId)
            ->first();
    }

    public function getUpcomingForAgent(string $deliveryAgentId, int $limit = 5): Collection
    {
        return Order::query()
            ->with(['customerInfo', 'financials', 'address.governorate', 'address.city', 'schedule'])
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereIn('status', [
                OrderStatusEnum::Assigned->value,
                OrderStatusEnum::OutForDelivery->value,
                OrderStatusEnum::AwaitingApproval->value,
            ])
            ->orderBy('assigned_at')
            ->limit($limit)
            ->get();
    }

    public function getTodayCollectedAmount(string $deliveryAgentId): float
    {
        return (float) DB::table('collections')
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereNull('deleted_at')
            ->whereDate('collected_at', today())
            ->sum('collected_amount');
    }

    public function countActiveOrders(string $deliveryAgentId): int
    {
        return Order::query()
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereIn('status', OrderStatusEnum::activeIds())
            ->count();
    }

    public function countDeliveredToday(string $deliveryAgentId): int
    {
        return Order::query()
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereIn('status', OrderStatusEnum::terminalIds())
            ->whereDate('delivered_at', today())
            ->count();
    }

    public function getWeeklyDeliveryRatePercent(string $deliveryAgentId): int
    {
        $since = now()->subDays(7);

        $terminal = Order::query()
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereIn('status', OrderStatusEnum::terminalIds())
            ->where('updated_at', '>=', $since)
            ->count();

        if ($terminal === 0) {
            return 0;
        }

        $delivered = Order::query()
            ->where('delivery_agent_id', $deliveryAgentId)
            ->whereIn('status', [
                OrderStatusEnum::Delivered->value,
                OrderStatusEnum::DeliveredPriceChanged->value,
                OrderStatusEnum::PartialDelivery->value,
            ])
            ->where('updated_at', '>=', $since)
            ->count();

        return (int) round(($delivered / $terminal) * 100);
    }

    public function listPostponedForAgent(
        string $deliveryAgentId,
        ?string $date,
        ?string $month,
    ): Collection {
        $query = Order::query()
            ->select('orders.*')
            ->with(['customerInfo', 'financials', 'address.city', 'schedule'])
            ->join('order_schedules', function ($join) {
                $join->on('orders.order_id', '=', 'order_schedules.order_id')
                    ->whereNull('order_schedules.deleted_at')
                    ->whereNotNull('order_schedules.postponed_date');
            })
            ->where('orders.delivery_agent_id', $deliveryAgentId)
            ->where('orders.status', OrderStatusEnum::Postponed->value);

        if ($date !== null && $date !== '') {
            $query->whereDate('order_schedules.postponed_date', $date);
        }

        if ($month !== null && $month !== '') {
            $parsed = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $query->whereBetween('order_schedules.postponed_date', [
                $parsed->toDateString(),
                $parsed->copy()->endOfMonth()->toDateString(),
            ]);
        }

        return $query
            ->orderBy('order_schedules.postponed_date')
            ->orderBy('orders.reference_code')
            ->get();
    }

    public function getPostponedCalendarForAgent(string $deliveryAgentId, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = DB::table('orders')
            ->join('order_schedules', function ($join) {
                $join->on('orders.order_id', '=', 'order_schedules.order_id')
                    ->whereNull('order_schedules.deleted_at')
                    ->whereNotNull('order_schedules.postponed_date');
            })
            ->where('orders.delivery_agent_id', $deliveryAgentId)
            ->where('orders.status', OrderStatusEnum::Postponed->value)
            ->whereNull('orders.deleted_at')
            ->whereBetween('order_schedules.postponed_date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->selectRaw('DATE(order_schedules.postponed_date) as postponed_day, COUNT(*) as order_count')
            ->groupBy(DB::raw('DATE(order_schedules.postponed_date)'))
            ->orderBy('postponed_day')
            ->get();

        $dates = [];
        $total = 0;

        foreach ($rows as $row) {
            $day = (string) $row->postponed_day;
            $count = (int) $row->order_count;
            $dates[$day] = $count;
            $total += $count;
        }

        return [
            'month' => $month,
            'total_postponed' => $total,
            'dates' => $dates,
        ];
    }

    private function applyStatusFilter($query, ?string $statusFilter): void
    {
        $filter = $statusFilter ?: 'all';

        match ($filter) {
            'all' => null,
            'new' => $query->where('status', OrderStatusEnum::Assigned->value),
            'in_delivery' => $query->where('status', OrderStatusEnum::OutForDelivery->value),
            'postponed' => $query->where('status', OrderStatusEnum::Postponed->value),
            'finished_orders' => $query->whereIn('status', [
                OrderStatusEnum::Delivered->value,
                OrderStatusEnum::DeliveredPriceChanged->value,
                OrderStatusEnum::PartialDelivery->value,
                OrderStatusEnum::RefusedPaidShipping->value,
                OrderStatusEnum::RefusedNoPayment->value,
            ]),
            'returned_orders' => $query->whereIn('status', [
                OrderStatusEnum::PartialDelivery->value,
                OrderStatusEnum::RefusedPaidShipping->value,
                OrderStatusEnum::RefusedNoPayment->value,
                OrderStatusEnum::CustomerCancelled->value,
                OrderStatusEnum::UnsafeArea->value,
                OrderStatusEnum::OutsideGovernorate->value,
            ]),
            default => null,
        };
    }

    private function applySearch($query, ?string $search): void
    {
        if ($search === null || trim($search) === '') {
            return;
        }

        $term = '%' . trim($search) . '%';

        $query->where(function ($builder) use ($term) {
            $builder
                ->where('reference_code', 'like', $term)
                ->orWhere('reference_no', 'like', $term)
                ->orWhereHas('customerInfo', function ($customerQuery) use ($term) {
                    $customerQuery
                        ->where('customer_name', 'like', $term)
                        ->orWhere('customer_phone', 'like', $term)
                        ->orWhere('phone_alt', 'like', $term);
                });
        });
    }
}
