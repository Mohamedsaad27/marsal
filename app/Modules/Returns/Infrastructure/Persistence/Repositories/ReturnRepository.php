<?php

namespace App\Modules\Returns\Infrastructure\Persistence\Repositories;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReturnRepository implements ReturnRepositoryInterface
{
    private const LIST_RELATIONS = [
        'order.customerInfo',
        'order.address.governorate',
        'order.address.city',
        'deliveryAgent.user',
        'shippingCompany.user',
    ];

    public function stats(): array
    {
        $base = $this->returnsPageQuery();
        $total = (clone $base)->count();

        return [
            'total' => $total,
            'pending' => (clone $base)->where('return_status', ReturnStatusEnum::Pending->value)->count(),
            'received_by_admin' => (clone $base)->where('return_status', ReturnStatusEnum::ReceivedByAdmin->value)->count(),
            'sent_to_company' => (clone $base)->where('return_status', ReturnStatusEnum::SentToCompany->value)->count(),
        ];
    }

    public function paginate(
        ?int $status,
        ?string $companyId,
        ?string $agentId,
        int $perPage,
    ): LengthAwarePaginator {
        $query = $this->returnsPageQuery()
            ->with(self::LIST_RELATIONS)
            ->orderByDesc('returns.created_at');

        if ($status !== null) {
            $query->where('returns.return_status', $status);
        }

        if ($companyId !== null) {
            $query->where('returns.shipping_company_id', $companyId);
        }

        if ($agentId !== null) {
            $query->where('returns.delivery_agent_id', $agentId);
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(string $returnId): OrderReturn
    {
        return OrderReturn::query()
            ->with(self::LIST_RELATIONS)
            ->where('return_id', $returnId)
            ->firstOrFail();
    }

    public function markReceived(string $returnId): OrderReturn
    {
        $record = $this->findOrFail($returnId);
        $record->update([
            'return_status' => ReturnStatusEnum::ReceivedByAdmin->value,
            'received_at' => Carbon::now(),
        ]);

        return $record->fresh(self::LIST_RELATIONS);
    }

    public function markSentToCompany(string $returnId): OrderReturn
    {
        $record = $this->findOrFail($returnId);
        $record->update([
            'return_status' => ReturnStatusEnum::SentToCompany->value,
            'returned_to_company_at' => Carbon::now(),
        ]);

        return $record->fresh(self::LIST_RELATIONS);
    }

    private function returnsPageQuery(): Builder
    {
        return OrderReturn::query()
            ->select('returns.*')
            ->addSelect('orders.status as order_status_id')
            ->join('orders', function ($join) {
                $join->on('orders.order_id', '=', 'returns.order_id')
                    ->whereNull('orders.deleted_at');
            })
            ->whereNotIn('orders.status', OrderStatusEnum::returnsPageExcludedIds());
    }
}
