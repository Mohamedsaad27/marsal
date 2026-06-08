<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use App\Modules\Dashboard\Domain\Helpers\PercentageChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetAvgDeliveryTimeQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

    /**
     * @return array{
     *     avg_hours: float|null,
     *     change_percent: float|null,
     *     change_direction: string|null,
     *     comparison_label: string|null
     * }
     */
    public function execute(): array
    {
        return $this->cache->remember('avg-delivery-time', fn () => $this->compute());
    }

    private function compute(): array
    {
        $now = Carbon::now();
        $thisWeekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $thisWeekEnd = $now->copy()->endOfWeek(Carbon::MONDAY)->endOfDay();
        $lastWeekStart = $thisWeekStart->copy()->subWeek();
        $lastWeekEnd = $thisWeekEnd->copy()->subWeek();

        $thisWeekAvg = $this->averageHours($thisWeekStart, $thisWeekEnd);
        $lastWeekAvg = $this->averageHours($lastWeekStart, $lastWeekEnd);

        $changePercent = null;
        $changeDirection = null;
        $comparisonLabel = null;

        if ($thisWeekAvg !== null && $lastWeekAvg !== null && $lastWeekAvg > 0) {
            $changePercent = PercentageChange::calculate($thisWeekAvg, $lastWeekAvg, 0);
            $changePercent = $changePercent !== null ? abs($changePercent) : null;

            if ($changePercent !== null) {
                if ($thisWeekAvg < $lastWeekAvg) {
                    $changeDirection = 'improvement';
                    $comparisonLabel = __('dashboard::dashboard.comparison_improvement', [
                        'percent' => $changePercent,
                    ]);
                } elseif ($thisWeekAvg > $lastWeekAvg) {
                    $changeDirection = 'regression';
                    $comparisonLabel = __('dashboard::dashboard.comparison_regression', [
                        'percent' => $changePercent,
                    ]);
                } else {
                    $changeDirection = 'unchanged';
                    $comparisonLabel = __('dashboard::dashboard.comparison_unchanged');
                }
            }
        }

        return [
            'avg_hours' => $thisWeekAvg !== null ? round($thisWeekAvg, 1) : null,
            'change_percent' => $changePercent,
            'change_direction' => $changeDirection,
            'comparison_label' => $comparisonLabel,
        ];
    }

    private function averageHours(Carbon $start, Carbon $end): ?float
    {
        $result = DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', OrderStatusEnum::Delivered->value)
            ->whereBetween('updated_at', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->value('avg_hours');

        return $result !== null ? (float) $result : null;
    }
}
