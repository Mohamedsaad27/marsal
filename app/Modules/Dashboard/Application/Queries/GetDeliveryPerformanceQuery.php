<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\DB;

class GetDeliveryPerformanceQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

    /**
     * @return array{
     *     success_rate: float|null,
     *     failed_count: int,
     *     pending_count: int,
     *     success_count: int
     * }
     */
    public function execute(): array
    {
        return $this->cache->remember('delivery-performance', fn () => $this->compute());
    }

    private function compute(): array
    {
        $base = DB::table('orders')->whereNull('deleted_at');

        $successCount = (int) (clone $base)
            ->where('status', OrderStatusEnum::Delivered->value)
            ->count();

        $failedCount = (int) (clone $base)
            ->whereIn('status', [
                OrderStatusEnum::Failed->value,
                OrderStatusEnum::Rejected->value,
            ])
            ->count();

        $pendingCount = (int) (clone $base)
            ->whereIn('status', [
                OrderStatusEnum::Pending->value,
                OrderStatusEnum::InDelivery->value,
                OrderStatusEnum::Postponed->value,
            ])
            ->count();

        $denominator = $successCount + $failedCount;
        $successRate = $denominator > 0
            ? round(($successCount / $denominator) * 100, 1)
            : null;

        return [
            'success_rate' => $successRate,
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
            'success_count' => $successCount,
        ];
    }
}
