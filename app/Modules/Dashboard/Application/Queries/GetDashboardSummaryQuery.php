<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use App\Modules\Dashboard\Domain\Helpers\PercentageChange;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetDashboardSummaryQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

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
            ->where('status', OrderStatusEnum::OutForDelivery->value)
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

        $companyBalances = DB::table('shipping_companies')
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(balance), 0) as signed_balance')
            ->selectRaw('COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END), 0) as system_payable')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN balance < 0 THEN balance ELSE 0 END), 0)) as company_payable')
            ->first();

        $thisMonthExposure = $this->companyExposureForPeriod($thisMonthStart, $thisMonthEnd);
        $lastMonthExposure = $this->companyExposureForPeriod($lastMonthStart, $lastMonthEnd);

        return [
            'total_orders' => $totalOrders,
            'total_orders_change_percent' => PercentageChange::calculate($ordersThisMonth, $ordersLastMonth),
            'in_delivery' => $inDelivery,
            'in_delivery_label' => __('dashboard::dashboard.in_delivery_label'),
            'delivered_this_week' => $deliveredThisWeek,
            'delivered_change_percent' => PercentageChange::calculate($deliveredThisWeek, $deliveredLastWeek),
            'signed_company_balance' => round((float) ($companyBalances->signed_balance ?? 0), 2),
            'system_payable_to_companies' => round((float) ($companyBalances->system_payable ?? 0), 2),
            'companies_payable_to_system' => round((float) ($companyBalances->company_payable ?? 0), 2),
            'system_payable_to_companies_change_percent' => PercentageChange::calculate(
                $thisMonthExposure['system_payable'],
                $lastMonthExposure['system_payable'],
            ),
            'companies_payable_to_system_change_percent' => PercentageChange::calculate(
                $thisMonthExposure['company_payable'],
                $lastMonthExposure['company_payable'],
            ),
        ];
    }

    private function companyExposureForPeriod(Carbon $from, Carbon $to): array
    {
        $exposure = DB::table('order_financials as of')
            ->join('orders as o', 'o.order_id', '=', 'of.order_id')
            ->whereNull('of.deleted_at')
            ->whereNull('o.deleted_at')
            ->whereBetween('o.updated_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(CASE WHEN of.net_due_company > 0 THEN of.net_due_company ELSE 0 END), 0) as system_payable')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN of.net_due_company < 0 THEN of.net_due_company ELSE 0 END), 0)) as company_payable')
            ->first();

        return [
            'system_payable' => (float) ($exposure->system_payable ?? 0),
            'company_payable' => (float) ($exposure->company_payable ?? 0),
        ];
    }
}
