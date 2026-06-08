<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use Illuminate\Support\Facades\DB;

class GetCollectionsBalanceQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

    /**
     * @return array{total_pending: float, currency: string, company_count: int}
     */
    public function execute(): array
    {
        return $this->cache->remember('collections-balance', fn () => $this->compute());
    }

    private function compute(): array
    {
        $aggregate = DB::table('shipping_companies')
            ->whereNull('deleted_at')
            ->where('balance', '>', 0)
            ->selectRaw('COALESCE(SUM(balance), 0) as total_pending, COUNT(*) as company_count')
            ->first();

        return [
            'total_pending' => round((float) ($aggregate->total_pending ?? 0), 2),
            'currency' => __('dashboard::dashboard.currency'),
            'company_count' => (int) ($aggregate->company_count ?? 0),
        ];
    }
}
