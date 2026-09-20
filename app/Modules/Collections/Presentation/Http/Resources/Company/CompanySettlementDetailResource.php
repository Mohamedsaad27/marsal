<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Company;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Collections\Infrastructure\Database\Models\SettlementItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Settlement */
class CompanySettlementDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->settlement_id,
            'reference' => 'STL-'.strtoupper(substr(str_replace('-', '', $this->settlement_id), 0, 8)),
            'status' => [
                'code' => $this->settlement_status?->value,
                'label' => $this->settlement_status?->labelAr(),
            ],
            'period_from' => $this->period_from?->toDateString(),
            'period_to' => $this->period_to?->toDateString(),
            'collections_count' => $this->resolveCollectionsCount(),
            'total_collections' => (float) $this->total_collections,
            'total_commissions' => (float) $this->total_commissions,
            'net_amount' => (float) $this->net_amount,
            'payment_direction' => $this->paymentDirection(),
            'payable_amount' => $this->payableAmount(),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'paid_at' => $this->paid_at?->toISOString(),
            'notes' => $this->notes,
            'collections' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->map(function (SettlementItem $item): array {
                    $collection = $item->collection;

                    return [
                        'collection_id' => $collection?->collection_id,
                        'order_code' => $collection?->order?->reference_code,
                        'order_id' => $collection?->order_id,
                        'collection_type' => [
                            'code' => $collection?->collection_type?->value,
                            'label' => $collection?->collection_type?->labelAr(),
                        ],
                        'collected_amount' => (float) $item->gross_amount,
                        'system_commission_amount' => (float) $item->commission_amount,
                        'company_net_due' => (float) $item->net_amount,
                        'company_settlement_status' => [
                            'code' => $this->settlement_status?->value,
                            'label' => $this->settlement_status?->labelAr(),
                        ],
                        'collected_at' => $collection?->collected_at?->toISOString(),
                    ];
                })->values()
            ),
            'created_at' => $this->created_at?->toISOString(),
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
