<?php

namespace App\Modules\Collections\Infrastructure\Persistence\Repositories;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Application\DTOs\SettlementFilterDTO;
use App\Modules\Collections\Application\Exceptions\NoCollectionsFoundForPeriodException;
use App\Modules\Collections\Application\Exceptions\SettlementInvalidStatusTransitionException;
use App\Modules\Collections\Application\Exceptions\SettlementItemsMismatchException;
use App\Modules\Collections\Application\Exceptions\SettlementNotFoundException;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Collections\Infrastructure\Database\Models\SettlementItem;
use App\Modules\Orders\Infrastructure\Database\Models\OrderFinancial;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettlementRepository implements SettlementRepositoryInterface
{
    private const LIST_RELATIONS = [
        'deliveryAgent.user',
        'shippingCompany.user',
        'initiatedBy',
    ];

    public function stats(): array
    {
        $base = Settlement::query();

        $pendingApproval = (clone $base)->where('settlement_status', SettlementStatusEnum::Draft->value);
        $approvedUnpaid = (clone $base)->where('settlement_status', SettlementStatusEnum::Approved->value);
        $paidThisMonth = (clone $base)
            ->where('settlement_status', SettlementStatusEnum::Paid->value)
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year);

        return [
            'all' => $this->directionalTotals(clone $base),
            'pending_approval' => $this->directionalTotals($pendingApproval),
            'approved_unpaid' => $this->directionalTotals($approvedUnpaid),
            'paid_this_month' => $this->directionalTotals($paidThisMonth),
        ];
    }

    public function paginate(SettlementFilterDTO $filter): LengthAwarePaginator
    {
        $query = Settlement::query()
            ->with(self::LIST_RELATIONS)
            ->withCount(['items as collections_count'])
            ->orderByDesc('created_at');

        $this->applyFilters($query, $filter);

        $paginator = $query->paginate($filter->perPage);

        $paginator->getCollection()->transform(function (Settlement $settlement) {
            $this->attachItemCount($settlement);

            return $settlement;
        });

        return $paginator;
    }

    public function findOrFail(string $settlementId): Settlement
    {
        $settlement = Settlement::query()
            ->with(self::LIST_RELATIONS)
            ->withCount(['items as collections_count'])
            ->where('settlement_id', $settlementId)
            ->first();

        if ($settlement === null) {
            throw new SettlementNotFoundException;
        }

        $this->attachItemCount($settlement);

        return $settlement;
    }

    public function createFromEligibleCollections(CreateSettlementDTO $dto): Settlement
    {
        return DB::transaction(function () use ($dto): Settlement {
            $collections = $this->eligibleCollectionsQuery($dto)
                ->orderBy('collected_at')
                ->lockForUpdate()
                ->get();

            if ($collections->isEmpty()) {
                throw new NoCollectionsFoundForPeriodException;
            }

            $settlement = Settlement::query()->create([
                'settlement_id' => (string) Str::uuid(),
                'settlement_type' => $dto->settlementType->value,
                'settlement_status' => SettlementStatusEnum::Draft->value,
                'delivery_agent_id' => $dto->settlementType === SettlementTypeEnum::Agent
                    ? $dto->referenceEntityId
                    : null,
                'shipping_company_id' => $dto->settlementType === SettlementTypeEnum::Company
                    ? $dto->referenceEntityId
                    : null,
                'initiated_by' => $dto->initiatedBy,
                'total_collections' => 0,
                'total_commissions' => 0,
                'net_amount' => 0,
                'period_from' => $dto->periodFrom,
                'period_to' => $dto->periodTo,
                'notes' => $dto->notes,
            ]);

            foreach ($collections as $collection) {
                SettlementItem::query()->create([
                    'settlement_item_id' => (string) Str::uuid(),
                    'settlement_id' => $settlement->settlement_id,
                    'collection_id' => $collection->collection_id,
                    'settlement_type' => $dto->settlementType->value,
                    'gross_amount' => $collection->collected_amount,
                    'commission_amount' => $dto->settlementType === SettlementTypeEnum::Agent
                        ? $collection->agent_commission_amount
                        : $collection->system_commission_amount,
                    'net_amount' => $dto->settlementType === SettlementTypeEnum::Agent
                        ? $collection->agent_net_due
                        : $collection->company_net_due,
                ]);
            }

            $totals = SettlementItem::query()
                ->where('settlement_id', $settlement->settlement_id)
                ->selectRaw('COALESCE(SUM(gross_amount), 0) as total_collections')
                ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commissions')
                ->selectRaw('COALESCE(SUM(net_amount), 0) as net_amount')
                ->firstOrFail();

            $settlement->update([
                'total_collections' => round((float) $totals->total_collections, 2),
                'total_commissions' => round((float) $totals->total_commissions, 2),
                'net_amount' => round((float) $totals->net_amount, 2),
            ]);

            $settlement->load(array_merge(self::LIST_RELATIONS, ['items']));
            $settlement->loadCount(['items as collections_count']);
            $this->attachItemCount($settlement);

            return $settlement;
        });
    }

    public function approve(string $settlementId): Settlement
    {
        return DB::transaction(function () use ($settlementId): Settlement {
            $settlement = Settlement::query()
                ->where('settlement_id', $settlementId)
                ->lockForUpdate()
                ->first();

            if ($settlement === null) {
                throw new SettlementNotFoundException;
            }

            if ($settlement->settlement_status !== SettlementStatusEnum::Draft) {
                throw new SettlementInvalidStatusTransitionException;
            }

            if (! SettlementItem::query()->where('settlement_id', $settlementId)->exists()) {
                throw new NoCollectionsFoundForPeriodException;
            }

            $settlement->update([
                'settlement_status' => SettlementStatusEnum::Approved->value,
            ]);

            $settlement = $settlement->fresh(array_merge(self::LIST_RELATIONS, ['items']));
            $settlement->loadCount(['items as collections_count']);
            $this->attachItemCount($settlement);

            return $settlement;
        });
    }

    public function markPaid(
        string $settlementId,
        string $paymentMethod,
        ?string $paymentReference,
        ?string $notes,
    ): Settlement {
        return DB::transaction(function () use ($settlementId, $paymentMethod, $paymentReference, $notes) {
            $settlement = Settlement::query()
                ->where('settlement_id', $settlementId)
                ->lockForUpdate()
                ->first();

            if ($settlement === null) {
                throw new SettlementNotFoundException;
            }

            if ($settlement->settlement_status !== SettlementStatusEnum::Approved) {
                throw new SettlementInvalidStatusTransitionException;
            }

            $items = SettlementItem::query()
                ->where('settlement_id', $settlement->settlement_id)
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw new NoCollectionsFoundForPeriodException;
            }

            $itemTotals = [
                'total_collections' => round((float) $items->sum('gross_amount'), 2),
                'total_commissions' => round((float) $items->sum('commission_amount'), 2),
                'net_amount' => round((float) $items->sum('net_amount'), 2),
            ];

            if (
                $itemTotals['total_collections'] !== round((float) $settlement->total_collections, 2)
                || $itemTotals['total_commissions'] !== round((float) $settlement->total_commissions, 2)
                || $itemTotals['net_amount'] !== round((float) $settlement->net_amount, 2)
            ) {
                throw new SettlementItemsMismatchException;
            }

            $signedNetAmount = round((float) $settlement->net_amount, 2);

            if ($settlement->settlement_type === SettlementTypeEnum::Agent && $settlement->delivery_agent_id !== null) {
                $agent = DeliveryAgent::query()
                    ->whereKey($settlement->delivery_agent_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($signedNetAmount !== 0.0) {
                    $agent->decrement('balance', $signedNetAmount);
                }
            }

            if ($settlement->settlement_type === SettlementTypeEnum::Company && $settlement->shipping_company_id !== null) {
                $company = ShippingCompany::query()
                    ->whereKey($settlement->shipping_company_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($signedNetAmount !== 0.0) {
                    $company->decrement('balance', $signedNetAmount);
                }
            }

            $isNoPayment = $signedNetAmount === 0.0;

            $settlement->update([
                'settlement_status' => SettlementStatusEnum::Paid->value,
                'payment_method' => $isNoPayment ? 'no_payment' : $paymentMethod,
                'payment_reference' => $isNoPayment ? null : $paymentReference,
                'notes' => $notes ?? $settlement->notes,
                'paid_at' => Carbon::now(),
            ]);

            $orderIds = Collection::query()
                ->whereIn('collection_id', $items->pluck('collection_id'))
                ->pluck('order_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $this->syncOrderSettlementCompletion($orderIds);

            $settlement = $settlement->fresh(array_merge(self::LIST_RELATIONS, ['items']));
            $settlement->loadCount(['items as collections_count']);
            $this->attachItemCount($settlement);

            return $settlement;
        });
    }

    public function findForCompany(string $settlementId, string $companyId): ?Settlement
    {
        $settlement = Settlement::query()
            ->with(array_merge(self::LIST_RELATIONS, ['items.collection.order']))
            ->withCount(['items as collections_count'])
            ->where('settlement_id', $settlementId)
            ->where('shipping_company_id', $companyId)
            ->where('settlement_type', SettlementTypeEnum::Company->value)
            ->first();

        if ($settlement === null) {
            return null;
        }

        $this->attachItemCount($settlement);

        return $settlement;
    }

    public function getLastPaidForCompany(string $companyId): ?array
    {
        $settlement = Settlement::query()
            ->where('shipping_company_id', $companyId)
            ->where('settlement_type', SettlementTypeEnum::Company->value)
            ->where('settlement_status', SettlementStatusEnum::Paid->value)
            ->orderByDesc('paid_at')
            ->first(['settlement_id', 'settlement_type', 'net_amount', 'paid_at']);

        if ($settlement === null) {
            return null;
        }

        return [
            'reference' => 'STL-'.strtoupper(substr(str_replace('-', '', $settlement->settlement_id), 0, 8)),
            'net_amount' => (float) $settlement->net_amount,
            'payment_direction' => $settlement->paymentDirection(),
            'payable_amount' => $settlement->payableAmount(),
            'paid_at' => $settlement->paid_at?->toISOString(),
        ];
    }

    private function eligibleCollectionsQuery(CreateSettlementDTO $dto): Builder
    {
        $query = Collection::query()
            ->whereDate('collected_at', '>=', $dto->periodFrom)
            ->whereDate('collected_at', '<=', $dto->periodTo)
            ->whereNotExists(function ($itemQuery) use ($dto): void {
                $itemQuery
                    ->selectRaw('1')
                    ->from('settlement_items')
                    ->whereColumn('settlement_items.collection_id', 'collections.collection_id')
                    ->where('settlement_items.settlement_type', $dto->settlementType->value);
            });

        if ($dto->settlementType === SettlementTypeEnum::Agent) {
            $query
                ->where('delivery_agent_id', $dto->referenceEntityId)
                ->where(function (Builder $eligibilityQuery): void {
                    $eligibilityQuery
                        ->where('agent_net_due', '<=', 0)
                        ->orWhereNotNull('cash_received_at');
                });
        } else {
            $query
                ->where('shipping_company_id', $dto->referenceEntityId)
                ->where(function (Builder $eligibilityQuery): void {
                    $eligibilityQuery
                        ->where('company_net_due', '<=', 0)
                        ->orWhereNotNull('cash_received_at');
                });
        }

        return $query;
    }

    private function attachItemCount(Settlement $settlement): void
    {
        $settlement->setAttribute(
            'eligible_collections_count',
            (int) ($settlement->collections_count ?? $settlement->items()->count()),
        );

        $settlement->syncOriginalAttribute('eligible_collections_count');
    }

    private function directionalTotals(Builder $query): array
    {
        $totals = $query
            ->selectRaw('COUNT(*) as settlements_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount > 0 THEN net_amount ELSE 0 END), 0) as agent_to_system_amount',
                [SettlementTypeEnum::Agent->value],
            )
            ->selectRaw(
                'ABS(COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount < 0 THEN net_amount ELSE 0 END), 0)) as system_to_agent_amount',
                [SettlementTypeEnum::Agent->value],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount > 0 THEN net_amount ELSE 0 END), 0) as system_to_company_amount',
                [SettlementTypeEnum::Company->value],
            )
            ->selectRaw(
                'ABS(COALESCE(SUM(CASE WHEN settlement_type = ? AND net_amount < 0 THEN net_amount ELSE 0 END), 0)) as company_to_system_amount',
                [SettlementTypeEnum::Company->value],
            )
            ->selectRaw('SUM(CASE WHEN net_amount = 0 THEN 1 ELSE 0 END) as no_payment_count')
            ->first();

        return [
            'settlements_count' => (int) ($totals?->settlements_count ?? 0),
            'agent_to_system_amount' => $this->money($totals?->agent_to_system_amount),
            'system_to_agent_amount' => $this->money($totals?->system_to_agent_amount),
            'system_to_company_amount' => $this->money($totals?->system_to_company_amount),
            'company_to_system_amount' => $this->money($totals?->company_to_system_amount),
            'no_payment_count' => (int) ($totals?->no_payment_count ?? 0),
        ];
    }

    private function money(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    /**
     * @param  string[]  $orderIds
     */
    private function syncOrderSettlementCompletion(array $orderIds): void
    {
        if ($orderIds === []) {
            return;
        }

        $paidTypesByOrder = DB::table('settlement_items as si')
            ->join('settlements as s', 's.settlement_id', '=', 'si.settlement_id')
            ->join('collections as c', 'c.collection_id', '=', 'si.collection_id')
            ->whereIn('c.order_id', $orderIds)
            ->where('s.settlement_status', SettlementStatusEnum::Paid->value)
            ->whereNull('s.deleted_at')
            ->whereNull('c.deleted_at')
            ->select(['c.order_id', 'si.settlement_type'])
            ->distinct()
            ->get()
            ->groupBy('order_id');

        foreach ($orderIds as $orderId) {
            $paidTypes = $paidTypesByOrder->get($orderId, collect())
                ->pluck('settlement_type')
                ->map(fn ($type): int => (int) $type);

            OrderFinancial::query()
                ->where('order_id', $orderId)
                ->update([
                    'is_settled' => $paidTypes->contains(SettlementTypeEnum::Agent->value)
                        && $paidTypes->contains(SettlementTypeEnum::Company->value),
                ]);
        }
    }

    private function applyFilters(Builder $query, SettlementFilterDTO $filter): void
    {
        if ($filter->settlementType !== null) {
            $query->where('settlement_type', $filter->settlementType);
        }

        if ($filter->status !== null) {
            $query->where('settlement_status', $filter->status);
        }

        if ($filter->companyId !== null) {
            $query->where('shipping_company_id', $filter->companyId)
                ->where('settlement_type', SettlementTypeEnum::Company->value);
        }

        if ($filter->dateFrom !== null) {
            $query->whereDate('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->whereDate('created_at', '<=', $filter->dateTo);
        }

        if ($filter->search !== null && $filter->search !== '') {
            $search = '%'.$filter->search.'%';

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('settlement_id', 'like', $search)
                    ->orWhere('payment_reference', 'like', $search)
                    ->orWhereHas('deliveryAgent.user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $search))
                    ->orWhereHas('shippingCompany', fn (Builder $companyQuery) => $companyQuery
                        ->where('company_name', 'like', $search)
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $search)));
            });
        }
    }
}
