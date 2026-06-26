<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Company;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection as CollectionModel;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Settlement */
class CompanySettlementDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->settlement_id,
            'reference'        => 'STL-' . strtoupper(substr(str_replace('-', '', $this->settlement_id), 0, 8)),
            'status'           => [
                'code'  => $this->settlement_status?->value,
                'label' => $this->settlement_status?->labelAr(),
            ],
            'period_from'      => $this->period_from?->toDateString(),
            'period_to'        => $this->period_to?->toDateString(),
            'collections_count' => $this->resolveCollectionsCount(),
            'total_collections' => (float) $this->total_collections,
            'total_commissions' => (float) $this->total_commissions,
            'net_amount'        => (float) $this->net_amount,
            'payment_method'    => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'paid_at'           => $this->paid_at?->toISOString(),
            'notes'             => $this->notes,
            'collections'       => $this->when(
                $this->relationLoaded('collections'),
                fn () => $this->collections->map(fn (CollectionModel $c) => [
                    'collection_id'    => $c->collection_id,
                    'order_code'       => $c->order?->reference_code,
                    'order_id'         => $c->order_id,
                    'collection_type'  => [
                        'code'  => $c->collection_type?->value,
                        'label' => $c->collection_type?->labelAr(),
                    ],
                    'collected_amount' => (float) $c->collected_amount,
                    'commission_amount' => (float) $c->commission_amount,
                    'net_due'          => (float) $c->net_due,
                    'collected_at'     => $c->collected_at?->toISOString(),
                ])->values()
            ),
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }

    private function resolveCollectionsCount(): int
    {
        if ($this->settlement_status === SettlementStatusEnum::Paid) {
            return (int) ($this->collections_count ?? 0);
        }

        return (int) ($this->eligible_collections_count ?? 0);
    }
}
