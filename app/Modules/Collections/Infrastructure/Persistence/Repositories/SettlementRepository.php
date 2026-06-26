<?php

namespace App\Modules\Collections\Infrastructure\Persistence\Repositories;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Application\DTOs\SettlementFilterDTO;
use App\Modules\Collections\Application\Exceptions\NoCollectionsFoundForPeriodException;
use App\Modules\Collections\Application\Exceptions\SettlementInvalidStatusTransitionException;
use App\Modules\Collections\Application\Exceptions\SettlementNotFoundException;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Orders\Infrastructure\Database\Models\OrderFinancial;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

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

        $totalAmount = (clone $base)->sum('net_amount');
        $pendingApproval = (clone $base)
            ->where('settlement_status', SettlementStatusEnum::Draft->value)
            ->sum('net_amount');
        $approvedUnpaid = (clone $base)
            ->where('settlement_status', SettlementStatusEnum::Approved->value)
            ->sum('net_amount');
        $paidThisMonth = (clone $base)
            ->where('settlement_status', SettlementStatusEnum::Paid->value)
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->sum('net_amount');

        return [
            'total_amount' => number_format((float) $totalAmount, 2, '.', ''),
            'pending_approval' => number_format((float) $pendingApproval, 2, '.', ''),
            'approved_unpaid' => number_format((float) $approvedUnpaid, 2, '.', ''),
            'paid_this_month' => number_format((float) $paidThisMonth, 2, '.', ''),
        ];
    }

    public function paginate(SettlementFilterDTO $filter): LengthAwarePaginator
    {
        $query = Settlement::query()
            ->with(self::LIST_RELATIONS)
            ->withCount('collections')
            ->orderByDesc('created_at');

        $this->applyFilters($query, $filter);

        $paginator = $query->paginate($filter->perPage);

        $paginator->getCollection()->transform(function (Settlement $settlement) {
            if ($settlement->settlement_status !== SettlementStatusEnum::Paid) {
                $settlement->setAttribute(
                    'eligible_collections_count',
                    $this->countEligibleCollections($settlement),
                );
            }

            return $settlement;
        });

        return $paginator;
    }

    public function findOrFail(string $settlementId): Settlement
    {
        $settlement = Settlement::query()
            ->with(self::LIST_RELATIONS)
            ->withCount('collections')
            ->where('settlement_id', $settlementId)
            ->first();

        if ($settlement === null) {
            throw new SettlementNotFoundException();
        }

        if ($settlement->settlement_status !== SettlementStatusEnum::Paid) {
            $settlement->setAttribute(
                'eligible_collections_count',
                $this->countEligibleCollections($settlement),
            );
        }

        return $settlement;
    }

    public function findEligibleCollections(CreateSettlementDTO $dto): SupportCollection
    {
        return $this->eligibleCollectionsQuery($dto)
            ->orderBy('collected_at')
            ->get();
    }

    public function createFromCollections(CreateSettlementDTO $dto, SupportCollection $collections): Settlement
    {
        if ($collections->isEmpty()) {
            throw new NoCollectionsFoundForPeriodException();
        }

        $totalCollections = round((float) $collections->sum('collected_amount'), 2);
        $totalCommissions = round((float) $collections->sum('commission_amount'), 2);
        $netAmount = round((float) $collections->sum('net_due'), 2);

        $settlement = Settlement::query()->create([
            'settlement_type' => $dto->settlementType->value,
            'settlement_status' => SettlementStatusEnum::Draft->value,
            'delivery_agent_id' => $dto->settlementType === SettlementTypeEnum::Agent
                ? $dto->referenceEntityId
                : null,
            'shipping_company_id' => $dto->settlementType === SettlementTypeEnum::Company
                ? $dto->referenceEntityId
                : null,
            'initiated_by' => $dto->initiatedBy,
            'total_collections' => $totalCollections,
            'total_commissions' => $totalCommissions,
            'net_amount' => $netAmount,
            'period_from' => $dto->periodFrom,
            'period_to' => $dto->periodTo,
            'notes' => $dto->notes,
        ]);

        $settlement->load(self::LIST_RELATIONS);
        $settlement->setAttribute('eligible_collections_count', $collections->count());

        return $settlement;
    }

    public function approve(string $settlementId): Settlement
    {
        $settlement = $this->findOrFail($settlementId);

        if ($settlement->settlement_status !== SettlementStatusEnum::Draft) {
            throw new SettlementInvalidStatusTransitionException();
        }

        $settlement->update([
            'settlement_status' => SettlementStatusEnum::Approved->value,
        ]);

        return $settlement->fresh(array_merge(self::LIST_RELATIONS, ['collections']));
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
                throw new SettlementNotFoundException();
            }

            if ($settlement->settlement_status !== SettlementStatusEnum::Approved) {
                throw new SettlementInvalidStatusTransitionException();
            }

            $collections = $this->buildEligibleCollectionsQueryForSettlement($settlement)
                ->lockForUpdate()
                ->get();

            if ($collections->isEmpty()) {
                throw new NoCollectionsFoundForPeriodException();
            }

            $orderIds = $collections->pluck('order_id')->filter()->all();

            Collection::query()
                ->whereIn('collection_id', $collections->pluck('collection_id'))
                ->update(['settlement_id' => $settlement->settlement_id]);

            if ($orderIds !== []) {
                OrderFinancial::query()
                    ->whereIn('order_id', $orderIds)
                    ->update(['is_settled' => true]);
            }

            if ($settlement->settlement_type === SettlementTypeEnum::Agent && $settlement->delivery_agent_id !== null) {
                DeliveryAgent::query()
                    ->whereKey($settlement->delivery_agent_id)
                    ->decrement('balance', $settlement->net_amount);
            }

            if ($settlement->settlement_type === SettlementTypeEnum::Company && $settlement->shipping_company_id !== null) {
                ShippingCompany::query()
                    ->whereKey($settlement->shipping_company_id)
                    ->decrement('balance', $settlement->net_amount);
            }

            $settlement->update([
                'settlement_status' => SettlementStatusEnum::Paid->value,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'notes' => $notes ?? $settlement->notes,
                'paid_at' => Carbon::now(),
            ]);

            return $settlement->fresh(array_merge(self::LIST_RELATIONS, ['collections']));
        });
    }

    public function countEligibleCollections(Settlement $settlement): int
    {
        return $this->buildEligibleCollectionsQueryForSettlement($settlement)->count();
    }

    public function findForCompany(string $settlementId, string $companyId): ?Settlement
    {
        $settlement = Settlement::query()
            ->with(array_merge(self::LIST_RELATIONS, ['collections.order']))
            ->withCount('collections')
            ->where('settlement_id', $settlementId)
            ->where('shipping_company_id', $companyId)
            ->where('settlement_type', SettlementTypeEnum::Company->value)
            ->first();

        if ($settlement === null) {
            return null;
        }

        if ($settlement->settlement_status !== SettlementStatusEnum::Paid) {
            $settlement->setAttribute(
                'eligible_collections_count',
                $this->countEligibleCollections($settlement),
            );
        }

        return $settlement;
    }

    public function getLastPaidForCompany(string $companyId): ?array
    {
        $settlement = Settlement::query()
            ->where('shipping_company_id', $companyId)
            ->where('settlement_type', SettlementTypeEnum::Company->value)
            ->where('settlement_status', SettlementStatusEnum::Paid->value)
            ->orderByDesc('paid_at')
            ->first(['settlement_id', 'net_amount', 'paid_at']);

        if ($settlement === null) {
            return null;
        }

        return [
            'reference'  => 'STL-' . strtoupper(substr(str_replace('-', '', $settlement->settlement_id), 0, 8)),
            'net_amount' => (float) $settlement->net_amount,
            'paid_at'    => $settlement->paid_at?->toISOString(),
        ];
    }

    private function eligibleCollectionsQuery(CreateSettlementDTO $dto): Builder
    {
        $query = Collection::query()
            ->whereNotNull('cash_received_at')
            ->whereNull('settlement_id')
            ->whereDate('collected_at', '>=', $dto->periodFrom)
            ->whereDate('collected_at', '<=', $dto->periodTo);

        if ($dto->settlementType === SettlementTypeEnum::Agent) {
            $query->where('delivery_agent_id', $dto->referenceEntityId);
        } else {
            $query->where('shipping_company_id', $dto->referenceEntityId);
        }

        return $query;
    }

    private function buildEligibleCollectionsQueryForSettlement(Settlement $settlement): Builder
    {
        $query = Collection::query()
            ->whereNotNull('cash_received_at')
            ->whereNull('settlement_id')
            ->whereDate('collected_at', '>=', $settlement->period_from)
            ->whereDate('collected_at', '<=', $settlement->period_to);

        if ($settlement->settlement_type === SettlementTypeEnum::Agent) {
            $query->where('delivery_agent_id', $settlement->delivery_agent_id);
        } else {
            $query->where('shipping_company_id', $settlement->shipping_company_id);
        }

        return $query;
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
            $search = '%' . $filter->search . '%';

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
