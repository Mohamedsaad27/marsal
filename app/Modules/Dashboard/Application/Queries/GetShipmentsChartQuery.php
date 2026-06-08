<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetShipmentsChartQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

    /**
     * @return array{labels: list<string>, delivered: list<int>, pending: list<int>}
     */
    public function execute(string $period = 'week'): array
    {
        return $this->cache->remember("shipments-chart:{$period}", fn () => $this->compute($period));
    }

    private function compute(string $period): array
    {
        if ($period !== 'week') {
            $period = 'week';
        }

        $weekStart = Carbon::now()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $labels = [];
        $delivered = [];
        $pending = [];

        for ($day = 0; $day < 7; $day++) {
            $date = $weekStart->copy()->addDays($day);
            $dayKey = $date->toDateString();
            $dayIndex = $date->dayOfWeek;

            $labels[] = __('dashboard::dashboard.weekdays.'.$dayIndex);

            $delivered[] = (int) DB::table('orders')
                ->whereNull('deleted_at')
                ->where('status', OrderStatusEnum::Delivered->value)
                ->whereDate('updated_at', $dayKey)
                ->count();

            $pending[] = (int) DB::table('orders')
                ->whereNull('deleted_at')
                ->whereIn('status', [
                    OrderStatusEnum::Pending->value,
                    OrderStatusEnum::Postponed->value,
                ])
                ->whereDate('created_at', $dayKey)
                ->count();
        }

        return [
            'labels' => $labels,
            'delivered' => $delivered,
            'pending' => $pending,
        ];
    }
}
