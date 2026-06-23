<?php

namespace App\Modules\Returns\Infrastructure\Persistence\Repositories;

use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReturnRepository implements ReturnRepositoryInterface
{
    private const LIST_RELATIONS = [
        'deliveryAgent.user',
        'shippingCompany.user',
    ];

    public function stats(): array
    {
        $base  = OrderReturn::query();
        $total = (clone $base)->count();

        return [
            'total'             => $total,
            'pending'           => (clone $base)->where('return_status', ReturnStatusEnum::Pending->value)->count(),
            'received_by_admin' => (clone $base)->where('return_status', ReturnStatusEnum::ReceivedByAdmin->value)->count(),
            'sent_to_company'   => (clone $base)->where('return_status', ReturnStatusEnum::SentToCompany->value)->count(),
        ];
    }

    public function paginate(
        ?int $status,
        ?string $companyId,
        ?string $agentId,
        int $perPage,
    ): LengthAwarePaginator {
        $query = OrderReturn::query()
            ->with(self::LIST_RELATIONS)
            ->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('return_status', $status);
        }

        if ($companyId !== null) {
            $query->where('shipping_company_id', $companyId);
        }

        if ($agentId !== null) {
            $query->where('delivery_agent_id', $agentId);
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
            'received_at'   => Carbon::now(),
        ]);

        return $record->fresh(self::LIST_RELATIONS);
    }

    public function markSentToCompany(string $returnId): OrderReturn
    {
        $record = $this->findOrFail($returnId);
        $record->update([
            'return_status'          => ReturnStatusEnum::SentToCompany->value,
            'returned_to_company_at' => Carbon::now(),
        ]);

        return $record->fresh(self::LIST_RELATIONS);
    }
}
