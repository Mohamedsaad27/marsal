<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use App\Modules\Dashboard\Domain\Helpers\PercentageChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetDashboardSummaryQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

    /**
     * @return array{
     *     total_orders: int,
     *     total_orders_change_percent: float|null,
     *     in_delivery: int,
     *     in_delivery_label: string,
     *     delivered_this_week: int,
     *     delivered_change_percent: float|null,
     *     net_balance_companies: float,
     *     net_balance_change_percent: float|null
     * }
     */
    public function execute(): array
    {
        return $this->cache->remember('summary', fn () => $this->compute());
    }

    private function compute(): array
    {
        $now = Carbon::now();
        $isoWeekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $isoWeekEnd = $now->copy()->endOfWeek(Carbon::MONDAY)->endOfDay();
        $lastIsoWeekStart = $isoWeekStart->copy()->subWeek();
        $lastIsoWeekEnd = $isoWeekEnd->copy()->subWeek();

        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $totalOrders = (int) DB::table('orders')->whereNull('deleted_at')->count();

        $ordersThisMonth = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
            ->count();

        $ordersLastMonth = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $inDelivery = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', OrderStatusEnum::InDelivery->value)
            ->count();

        $deliveredThisWeek = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', OrderStatusEnum::Delivered->value)
            ->whereBetween('updated_at', [$isoWeekStart, $isoWeekEnd])
            ->count();

        $deliveredLastWeek = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', OrderStatusEnum::Delivered->value)
            ->whereBetween('updated_at', [$lastIsoWeekStart, $lastIsoWeekEnd])
            ->count();

        $netBalanceCompanies = (float) (DB::table('shipping_companies')
            ->whereNull('deleted_at')
            ->sum('balance') ?? 0);

        $netDueThisMonth = (float) (DB::table('order_financials as of')
            ->join('orders as o', 'o.order_id', '=', 'of.order_id')
            ->whereNull('of.deleted_at')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.updated_at', [$thisMonthStart, $thisMonthEnd])
            ->sum('of.net_due_company') ?? 0);

        $netDueLastMonth = (float) (DB::table('order_financials as of')
            ->join('orders as o', 'o.order_id', '=', 'of.order_id')
            ->whereNull('of.deleted_at')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.updated_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('of.net_due_company') ?? 0);

        return [
            'total_orders' => $totalOrders,
            'total_orders_change_percent' => PercentageChange::calculate($ordersThisMonth, $ordersLastMonth),
            'in_delivery' => $inDelivery,
            'in_delivery_label' => __('dashboard::dashboard.in_delivery_label'),
            'delivered_this_week' => $deliveredThisWeek,
            'delivered_change_percent' => PercentageChange::calculate($deliveredThisWeek, $deliveredLastWeek),
            'net_balance_companies' => round($netBalanceCompanies, 2),
            'net_balance_change_percent' => PercentageChange::calculate($netDueThisMonth, $netDueLastMonth),
        ];
    }
}
