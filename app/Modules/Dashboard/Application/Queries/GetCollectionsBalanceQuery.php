<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use Illuminate\Support\Facades\DB;

class GetCollectionsBalanceQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

    public function execute(): array
    {
        return $this->cache->remember('collections-balance', fn () => $this->compute());
    }

    private function compute(): array
    {
        $aggregate = DB::table('shipping_companies')
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(balance), 0) as signed_company_balance')
            ->selectRaw('COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END), 0) as system_payable_to_companies')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN balance < 0 THEN balance ELSE 0 END), 0)) as companies_payable_to_system')
            ->selectRaw('SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END) as creditor_company_count')
            ->selectRaw('SUM(CASE WHEN balance < 0 THEN 1 ELSE 0 END) as debtor_company_count')
            ->first();

        return [
            'signed_company_balance' => round((float) ($aggregate->signed_company_balance ?? 0), 2),
            'system_payable_to_companies' => round((float) ($aggregate->system_payable_to_companies ?? 0), 2),
            'companies_payable_to_system' => round((float) ($aggregate->companies_payable_to_system ?? 0), 2),
            'currency' => __('dashboard::dashboard.currency'),
            'creditor_company_count' => (int) ($aggregate->creditor_company_count ?? 0),
            'debtor_company_count' => (int) ($aggregate->debtor_company_count ?? 0),
        ];
    }
}
