<?php

namespace App\Modules\Reports\Infrastructure\Persistence\Repositories;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Reports\Application\DTOs\ReportFilterDTO;
use App\Modules\Reports\Domain\Interfaces\ReportsRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportsRepository implements ReportsRepositoryInterface
{
    public function orders(ReportFilterDTO $filter): array
    {
        $query = Order::query()
            ->with([
                'customerInfo',
                'financials',
                'address.governorate',
                'address.city',
                'shippingCompany.user',
                'deliveryAgent.user',
            ])
            ->orderByDesc('orders.created_at');

        $this->applyOrderFilters($query, $filter);

        $summary = $this->ordersSummary(clone $query);
        $paginator = $query->paginate($filter->perPage);

        return compact('summary', 'paginator');
    }

    public function collections(ReportFilterDTO $filter): array
    {
        $query = Collection::query()
            ->with([
                'order',
                'deliveryAgent.user',
                'shippingCompany.user',
                'agentSettlementItem.settlement',
                'companySettlementItem.settlement',
            ])
            ->orderByDesc('collected_at')
            ->orderByDesc('created_at');

        $this->applyCollectionFilters($query, $filter);

        $summary = $this->collectionsSummary(clone $query);
        $paginator = $query->paginate($filter->perPage);

        return compact('summary', 'paginator');
    }

    public function settlements(ReportFilterDTO $filter): array
    {
        $query = Settlement::query()
            ->with(['deliveryAgent.user', 'shippingCompany.user', 'initiatedBy'])
            ->withCount(['items as collections_count'])
            ->orderByDesc('settlements.created_at'); // ← fix الـ ambiguous من قبل

        $this->applySettlementFilters($query, $filter);

        // query منفصلة للـ summary بدون with/withCount
        $summaryQuery = Settlement::query();
        $this->applySettlementFilters($summaryQuery, $filter);
        $summary = $this->settlementsSummary($summaryQuery);

        $paginator = $query->paginate($filter->perPage);

        return compact('summary', 'paginator');
    }

    public function deliveryAgents(ReportFilterDTO $filter): array
    {
        $query = DeliveryAgent::query()
            ->with(['user', 'supervisor.user'])
            ->orderByDesc('created_at');

        $this->applyAgentFilters($query, $filter);

        $summary = $this->agentsSummary(clone $query, $filter);
        $paginator = $query->paginate($filter->perPage);
        $this->attachAgentMetrics($paginator, $filter);

        return compact('summary', 'paginator');
    }

    public function shippingCompanies(ReportFilterDTO $filter): array
    {
        $query = ShippingCompany::query()
            ->with('user')
            ->orderByDesc('created_at');

        $this->applyCompanyFilters($query, $filter);

        $summary = $this->companiesSummary(clone $query, $filter);
        $paginator = $query->paginate($filter->perPage);
        $this->attachCompanyMetrics($paginator, $filter);

        return compact('summary', 'paginator');
    }

    private function ordersSummary(Builder $query): array
    {
        $total = (clone $query)->count();
        $terminal = (clone $query)
            ->whereIn('status', array_map(
                fn (OrderStatusEnum $status) => $status->value,
                array_filter(OrderStatusEnum::cases(), fn (OrderStatusEnum $status) => $status->isTerminal()),
            ))
            ->count();

        $money = (clone $query)
            ->reorder()
            ->leftJoin('order_financials', 'orders.order_id', '=', 'order_financials.order_id')
            ->selectRaw('COALESCE(SUM(order_financials.original_amount), 0) as original_amount')
            ->selectRaw('COALESCE(SUM(order_financials.collected_amount), 0) as collected_amount')
            ->selectRaw('COALESCE(SUM(order_financials.agent_commission_amount), 0) as agent_commission_amount')
            ->selectRaw('COALESCE(SUM(order_financials.collected_amount - order_financials.agent_commission_amount), 0) as agent_net_due')
            ->selectRaw('COALESCE(SUM(order_financials.system_commission_amount), 0) as system_commission_amount')
            ->selectRaw('COALESCE(SUM(order_financials.net_due_company), 0) as company_net_due')
            ->selectRaw('COALESCE(SUM(CASE WHEN order_financials.collected_amount - order_financials.agent_commission_amount > 0 THEN order_financials.collected_amount - order_financials.agent_commission_amount ELSE 0 END), 0) as agent_to_system_amount')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN order_financials.collected_amount - order_financials.agent_commission_amount < 0 THEN order_financials.collected_amount - order_financials.agent_commission_amount ELSE 0 END), 0)) as system_to_agent_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN order_financials.net_due_company > 0 THEN order_financials.net_due_company ELSE 0 END), 0) as system_to_company_amount')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN order_financials.net_due_company < 0 THEN order_financials.net_due_company ELSE 0 END), 0)) as company_to_system_amount')
            ->first();

        return [
            'total_orders' => $total,
            'terminal_orders' => $terminal,
            'pending_orders' => max($total - $terminal, 0),
            'total_original_amount' => $this->money($money?->original_amount),
            'total_collected_amount' => $this->money($money?->collected_amount),
            'total_agent_commission_amount' => $this->money($money?->agent_commission_amount),
            'total_agent_net_due' => $this->money($money?->agent_net_due),
            'total_system_commission_amount' => $this->money($money?->system_commission_amount),
            'total_company_net_due' => $this->money($money?->company_net_due),
            'agent_to_system_amount' => $this->money($money?->agent_to_system_amount),
            'system_to_agent_amount' => $this->money($money?->system_to_agent_amount),
            'system_to_company_amount' => $this->money($money?->system_to_company_amount),
            'company_to_system_amount' => $this->money($money?->company_to_system_amount),
        ];
    }

    private function collectionsSummary(Builder $query): array
    {
        $money = (clone $query)
            ->reorder()
            ->selectRaw('COUNT(*) as total_collections')
            ->selectRaw('COALESCE(SUM(collected_amount), 0) as collected_amount')
            ->selectRaw('COALESCE(SUM(agent_commission_amount), 0) as agent_commission_amount')
            ->selectRaw('COALESCE(SUM(agent_net_due), 0) as agent_net_due')
            ->selectRaw('COALESCE(SUM(system_commission_amount), 0) as system_commission_amount')
            ->selectRaw('COALESCE(SUM(company_net_due), 0) as company_net_due')
            ->selectRaw('COALESCE(SUM(CASE WHEN agent_net_due > 0 THEN agent_net_due ELSE 0 END), 0) as agent_to_system_amount')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN agent_net_due < 0 THEN agent_net_due ELSE 0 END), 0)) as system_to_agent_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN company_net_due > 0 THEN company_net_due ELSE 0 END), 0) as system_to_company_amount')
            ->selectRaw('ABS(COALESCE(SUM(CASE WHEN company_net_due < 0 THEN company_net_due ELSE 0 END), 0)) as company_to_system_amount')
            ->first();

        return [
            'total_collections' => (int) ($money?->total_collections ?? 0),
            'total_collected_amount' => $this->money($money?->collected_amount),
            'total_agent_commission_amount' => $this->money($money?->agent_commission_amount),
            'total_agent_net_due' => $this->money($money?->agent_net_due),
            'total_system_commission_amount' => $this->money($money?->system_commission_amount),
            'total_company_net_due' => $this->money($money?->company_net_due),
            'agent_to_system_amount' => $this->money($money?->agent_to_system_amount),
            'system_to_agent_amount' => $this->money($money?->system_to_agent_amount),
            'system_to_company_amount' => $this->money($money?->system_to_company_amount),
            'company_to_system_amount' => $this->money($money?->company_to_system_amount),
            'pending_cash_count' => (clone $query)
                ->whereNull('cash_received_at')
                ->where('agent_net_due', '>', 0)
                ->count(),
            'agent_settled_count' => $this->paidSettlementCount(clone $query, SettlementTypeEnum::Agent),
            'company_settled_count' => $this->paidSettlementCount(clone $query, SettlementTypeEnum::Company),
            'fully_settled_count' => (clone $query)
                ->whereHas('agentSettlementItem.settlement', fn (Builder $settlement) => $settlement
                    ->where('settlement_status', SettlementStatusEnum::Paid->value))
                ->whereHas('companySettlementItem.settlement', fn (Builder $settlement) => $settlement
                    ->where('settlement_status', SettlementStatusEnum::Paid->value))
                ->count(),
        ];
    }

    private function settlementsSummary(Builder $query): array
    {
        // نبني subquery نظيفة من الـ query الأصلية بدون withCount وبدون selects زيادة
        $summaryQuery = DB::table(
            DB::raw("({$query->toBase()->toSql()}) as sub")
        )->mergeBindings($query->toBase());

        $summary = $summaryQuery->selectRaw('
            COUNT(*) as total_settlements,
            COALESCE(SUM(CASE WHEN settlement_type = ? THEN total_collections ELSE 0 END), 0) as agent_total_collections,
            COALESCE(SUM(CASE WHEN settlement_type = ? THEN total_commissions ELSE 0 END), 0) as total_agent_commission_amount,
            COALESCE(SUM(CASE WHEN settlement_type = ? THEN net_amount ELSE 0 END), 0) as total_agent_net_amount,
            COALESCE(SUM(CASE WHEN settlement_type = ? THEN total_collections ELSE 0 END), 0) as company_total_collections,
            COALESCE(SUM(CASE WHEN settlement_type = ? THEN total_commissions ELSE 0 END), 0) as total_system_commission_amount,
            COALESCE(SUM(CASE WHEN settlement_type = ? THEN net_amount ELSE 0 END), 0) as total_company_net_amount,
            COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount > 0 THEN net_amount ELSE 0 END), 0) as agent_to_system_amount,
            ABS(COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount < 0 THEN net_amount ELSE 0 END), 0)) as system_to_agent_amount,
            COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount > 0 THEN net_amount ELSE 0 END), 0) as system_to_company_amount,
            ABS(COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount < 0 THEN net_amount ELSE 0 END), 0)) as company_to_system_amount,
            SUM(CASE WHEN net_amount = 0 THEN 1 ELSE 0 END) as no_payment_count
        ', [
            SettlementTypeEnum::Agent->value,
            SettlementTypeEnum::Agent->value,
            SettlementTypeEnum::Agent->value,
            SettlementTypeEnum::Company->value,
            SettlementTypeEnum::Company->value,
            SettlementTypeEnum::Company->value,
            SettlementTypeEnum::Agent->value,
            SettlementTypeEnum::Agent->value,
            SettlementTypeEnum::Company->value,
            SettlementTypeEnum::Company->value,
        ])->first();

        return [
            'total_settlements' => (int) $summary->total_settlements,
            'agent_total_collections' => $this->money($summary->agent_total_collections),
            'total_agent_commission_amount' => $this->money($summary->total_agent_commission_amount),
            'total_agent_net_amount' => $this->money($summary->total_agent_net_amount),
            'company_total_collections' => $this->money($summary->company_total_collections),
            'total_system_commission_amount' => $this->money($summary->total_system_commission_amount),
            'total_company_net_amount' => $this->money($summary->total_company_net_amount),
            'agent_to_system_amount' => $this->money($summary->agent_to_system_amount),
            'system_to_agent_amount' => $this->money($summary->system_to_agent_amount),
            'system_to_company_amount' => $this->money($summary->system_to_company_amount),
            'company_to_system_amount' => $this->money($summary->company_to_system_amount),
            'no_payment_count' => (int) $summary->no_payment_count,
        ];
    }

    private function agentsSummary(Builder $query, ReportFilterDTO $filter): array
    {
        $agentIds = (clone $query)->pluck('delivery_agent_id')->all();

        return [
            'total_agents' => count($agentIds),
            'available_agents' => (clone $query)->where('is_available', 1)->count(),
            'signed_total_balance' => $this->money((clone $query)->sum('balance')),
            'agent_owes_system_amount' => $this->money((clone $query)
                ->where('balance', '>', 0)
                ->sum('balance')),
            'system_owes_agents_amount' => $this->money(abs((float) (clone $query)
                ->where('balance', '<', 0)
                ->sum('balance'))),
            'total_orders' => $this->countOrdersForEntities('delivery_agent_id', $agentIds, $filter),
            'total_collected_amount' => $this->sumCollectionsForEntities('delivery_agent_id', $agentIds, 'collected_amount', $filter),
        ];
    }

    private function companiesSummary(Builder $query, ReportFilterDTO $filter): array
    {
        $companyIds = (clone $query)->pluck('shipping_company_id')->all();

        return [
            'total_companies' => count($companyIds),
            'active_companies' => (clone $query)->where('is_active', 1)->count(),
            'signed_total_balance' => $this->money((clone $query)->sum('balance')),
            'system_owes_companies_amount' => $this->money((clone $query)
                ->where('balance', '>', 0)
                ->sum('balance')),
            'companies_owe_system_amount' => $this->money(abs((float) (clone $query)
                ->where('balance', '<', 0)
                ->sum('balance'))),
            'total_orders' => $this->countOrdersForEntities('shipping_company_id', $companyIds, $filter),
            'total_collected_amount' => $this->sumCollectionsForEntities('shipping_company_id', $companyIds, 'collected_amount', $filter),
        ];
    }

    private function attachAgentMetrics(LengthAwarePaginator $paginator, ReportFilterDTO $filter): void
    {
        $ids = $paginator->getCollection()->pluck('delivery_agent_id')->all();
        $metrics = $this->entityMetrics('delivery_agent_id', $ids, $filter, SettlementTypeEnum::Agent);

        $paginator->getCollection()->each(function (DeliveryAgent $agent) use ($metrics): void {
            $agent->setAttribute(
                'report_metrics',
                $metrics[$agent->delivery_agent_id] ?? $this->emptyMetrics(SettlementTypeEnum::Agent),
            );
        });
    }

    private function attachCompanyMetrics(LengthAwarePaginator $paginator, ReportFilterDTO $filter): void
    {
        $ids = $paginator->getCollection()->pluck('shipping_company_id')->all();
        $metrics = $this->entityMetrics('shipping_company_id', $ids, $filter, SettlementTypeEnum::Company);

        $paginator->getCollection()->each(function (ShippingCompany $company) use ($metrics): void {
            $company->setAttribute(
                'report_metrics',
                $metrics[$company->shipping_company_id] ?? $this->emptyMetrics(SettlementTypeEnum::Company),
            );
        });
    }

    private function entityMetrics(
        string $entityColumn,
        array $ids,
        ReportFilterDTO $filter,
        SettlementTypeEnum $type,
    ): array {
        if ($ids === []) {
            return [];
        }

        $orders = Order::query()
            ->select($entityColumn)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(CASE WHEN status IN ('.$this->terminalStatusList().') THEN 1 ELSE 0 END) as terminal_orders')
            ->whereIn($entityColumn, $ids);
        $this->applyMetricDateFilter($orders, $filter, 'created_at');

        $orderMetrics = $orders
            ->groupBy($entityColumn)
            ->get()
            ->keyBy($entityColumn);

        $commissionColumn = $type === SettlementTypeEnum::Agent
            ? 'agent_commission_amount'
            : 'system_commission_amount';
        $netColumn = $type === SettlementTypeEnum::Agent ? 'agent_net_due' : 'company_net_due';

        $collections = Collection::query()
            ->select($entityColumn)
            ->selectRaw('COALESCE(SUM(collected_amount), 0) as collected_amount')
            ->selectRaw("COALESCE(SUM({$commissionColumn}), 0) as commission_amount")
            ->selectRaw("COALESCE(SUM({$netColumn}), 0) as net_due")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$netColumn} > 0 THEN {$netColumn} ELSE 0 END), 0) as positive_net_due")
            ->selectRaw("ABS(COALESCE(SUM(CASE WHEN {$netColumn} < 0 THEN {$netColumn} ELSE 0 END), 0)) as negative_net_due")
            ->whereIn($entityColumn, $ids);
        $this->applyMetricDateFilter($collections, $filter, 'collected_at');

        $collectionMetrics = $collections
            ->groupBy($entityColumn)
            ->get()
            ->keyBy($entityColumn);

        $metrics = [];

        foreach ($ids as $id) {
            $orderMetric = $orderMetrics->get($id);
            $collectionMetric = $collectionMetrics->get($id);

            $commonMetrics = [
                'total_orders' => (int) ($orderMetric?->total_orders ?? 0),
                'terminal_orders' => (int) ($orderMetric?->terminal_orders ?? 0),
                'total_collected_amount' => $this->money($collectionMetric?->collected_amount),
            ];

            $metrics[$id] = $type === SettlementTypeEnum::Agent
                ? array_merge($commonMetrics, [
                    'total_agent_commission_amount' => $this->money($collectionMetric?->commission_amount),
                    'total_agent_net_due' => $this->money($collectionMetric?->net_due),
                    'agent_to_system_amount' => $this->money($collectionMetric?->positive_net_due),
                    'system_to_agent_amount' => $this->money($collectionMetric?->negative_net_due),
                ])
                : array_merge($commonMetrics, [
                    'total_system_commission_amount' => $this->money($collectionMetric?->commission_amount),
                    'total_company_net_due' => $this->money($collectionMetric?->net_due),
                    'system_to_company_amount' => $this->money($collectionMetric?->positive_net_due),
                    'company_to_system_amount' => $this->money($collectionMetric?->negative_net_due),
                ]);
        }

        return $metrics;
    }

    private function applyOrderFilters(Builder $query, ReportFilterDTO $filter): void
    {
        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }

        if ($filter->agentId !== null) {
            $query->where('delivery_agent_id', $filter->agentId);
        }

        if ($filter->companyId !== null) {
            $query->where('shipping_company_id', $filter->companyId);
        }

        if ($filter->governorateId !== null) {
            $query->whereHas('address', fn (Builder $address) => $address->where('governorate_id', $filter->governorateId));
        }

        $this->applyMetricDateFilter($query, $filter, 'created_at');

        if ($filter->search !== null && trim($filter->search) !== '') {
            $search = '%'.trim($filter->search).'%';

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('reference_code', 'like', $search)
                    ->orWhere('reference_no', 'like', $search)
                    ->orWhereHas('customerInfo', fn (Builder $customer) => $customer
                        ->where('customer_name', 'like', $search)
                        ->orWhere('customer_phone', 'like', $search))
                    ->orWhereHas('shippingCompany', fn (Builder $company) => $company
                        ->where('company_name', 'like', $search)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $search)))
                    ->orWhereHas('deliveryAgent.user', fn (Builder $user) => $user->where('name', 'like', $search));
            });
        }
    }

    private function applyCollectionFilters(Builder $query, ReportFilterDTO $filter): void
    {
        if ($filter->collectionType !== null) {
            $query->where('collection_type', $filter->collectionType);
        }

        if ($filter->agentId !== null) {
            $query->where('delivery_agent_id', $filter->agentId);
        }

        if ($filter->companyId !== null) {
            $query->where('shipping_company_id', $filter->companyId);
        }

        $this->applyMetricDateFilter($query, $filter, 'collected_at');

        if ($filter->search !== null && trim($filter->search) !== '') {
            $search = '%'.trim($filter->search).'%';

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('collection_id', 'like', $search)
                    ->orWhereHas('order', fn (Builder $order) => $order->where('reference_code', 'like', $search))
                    ->orWhereHas('deliveryAgent.user', fn (Builder $user) => $user->where('name', 'like', $search))
                    ->orWhereHas('shippingCompany', fn (Builder $company) => $company
                        ->where('company_name', 'like', $search)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $search)));
            });
        }
    }

    private function applySettlementFilters(Builder $query, ReportFilterDTO $filter): void
    {
        if ($filter->settlementType !== null) {
            $query->where('settlement_type', $filter->settlementType);
        }

        if ($filter->status !== null) {
            $query->where('settlement_status', $filter->status);
        }

        if ($filter->agentId !== null) {
            $query->where('delivery_agent_id', $filter->agentId);
        }

        if ($filter->companyId !== null) {
            $query->where('shipping_company_id', $filter->companyId);
        }

        $this->applyMetricDateFilter($query, $filter, 'created_at');

        if ($filter->search !== null && trim($filter->search) !== '') {
            $search = '%'.trim($filter->search).'%';

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('settlement_id', 'like', $search)
                    ->orWhere('payment_reference', 'like', $search)
                    ->orWhereHas('deliveryAgent.user', fn (Builder $user) => $user->where('name', 'like', $search))
                    ->orWhereHas('shippingCompany', fn (Builder $company) => $company
                        ->where('company_name', 'like', $search)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $search)));
            });
        }
    }

    private function applyAgentFilters(Builder $query, ReportFilterDTO $filter): void
    {
        if ($filter->isActive !== null) {
            $query->where('is_available', $filter->isActive);
        }

        if ($filter->search !== null && trim($filter->search) !== '') {
            $search = '%'.trim($filter->search).'%';

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('national_id', 'like', $search)
                    ->orWhere('vehicle_plate_number', 'like', $search)
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }
    }

    private function applyCompanyFilters(Builder $query, ReportFilterDTO $filter): void
    {
        if ($filter->isActive !== null) {
            $query->where('is_active', $filter->isActive);
        }

        if ($filter->search !== null && trim($filter->search) !== '') {
            $search = '%'.trim($filter->search).'%';

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('company_name', 'like', $search)
                    ->orWhere('commercial_reg', 'like', $search)
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }
    }

    private function applyMetricDateFilter(Builder $query, ReportFilterDTO $filter, string $column): void
    {
        if ($filter->dateFrom !== null) {
            $query->whereDate($column, '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->whereDate($column, '<=', $filter->dateTo);
        }
    }

    private function countOrdersForEntities(string $entityColumn, array $ids, ReportFilterDTO $filter): int
    {
        if ($ids === []) {
            return 0;
        }

        $query = Order::query()->whereIn($entityColumn, $ids);
        $this->applyMetricDateFilter($query, $filter, 'created_at');

        return $query->count();
    }

    private function sumCollectionsForEntities(string $entityColumn, array $ids, string $amountColumn, ReportFilterDTO $filter): string
    {
        if ($ids === []) {
            return $this->money(0);
        }

        $query = Collection::query()->whereIn($entityColumn, $ids);
        $this->applyMetricDateFilter($query, $filter, 'collected_at');

        return $this->money($query->sum($amountColumn));
    }

    private function terminalStatusList(): string
    {
        return collect(OrderStatusEnum::cases())
            ->filter(fn (OrderStatusEnum $status) => $status->isTerminal())
            ->map(fn (OrderStatusEnum $status) => (string) $status->value)
            ->implode(',');
    }

    private function emptyMetrics(SettlementTypeEnum $type): array
    {
        $commonMetrics = [
            'total_orders' => 0,
            'terminal_orders' => 0,
            'total_collected_amount' => $this->money(0),
        ];

        return $type === SettlementTypeEnum::Agent
            ? array_merge($commonMetrics, [
                'total_agent_commission_amount' => $this->money(0),
                'total_agent_net_due' => $this->money(0),
                'agent_to_system_amount' => $this->money(0),
                'system_to_agent_amount' => $this->money(0),
            ])
            : array_merge($commonMetrics, [
                'total_system_commission_amount' => $this->money(0),
                'total_company_net_due' => $this->money(0),
                'system_to_company_amount' => $this->money(0),
                'company_to_system_amount' => $this->money(0),
            ]);
    }

    private function paidSettlementCount(Builder $query, SettlementTypeEnum $type): int
    {
        $relationship = $type === SettlementTypeEnum::Agent
            ? 'agentSettlementItem.settlement'
            : 'companySettlementItem.settlement';

        return $query
            ->whereHas($relationship, fn (Builder $settlement) => $settlement
                ->where('settlement_status', SettlementStatusEnum::Paid->value))
            ->count();
    }

    private function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
