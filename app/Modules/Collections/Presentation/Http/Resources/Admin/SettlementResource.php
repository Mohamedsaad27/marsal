<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Admin;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Settlement */
class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->settlement_id,
            'reference' => 'STL-' . strtoupper(substr(str_replace('-', '', $this->settlement_id), 0, 8)),
            'settlement_type' => [
                'code' => $this->settlement_type?->value,
                'label' => $this->settlement_type?->labelAr(),
            ],
            'status' => [
                'code' => $this->settlement_status?->value,
                'label' => $this->settlement_status?->labelAr(),
            ],
            'entity' => $this->resolveEntity(),
            'period_from' => $this->period_from?->toDateString(),
            'period_to' => $this->period_to?->toDateString(),
            'collections_count' => $this->resolveCollectionsCount(),
            'total_collections' => $this->total_collections,
            'total_commissions' => $this->total_commissions,
            'net_amount' => $this->net_amount,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'paid_at' => $this->paid_at?->toISOString(),
            'initiated_by' => $this->when(
                $this->relationLoaded('initiatedBy') && $this->initiatedBy !== null,
                fn () => [
                    'id' => $this->initiatedBy->user_id,
                    'name' => $this->initiatedBy->name,
                ],
            ),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function resolveEntity(): ?array
    {
        if ($this->settlement_type === SettlementTypeEnum::Agent) {
            if (! $this->relationLoaded('deliveryAgent') || $this->deliveryAgent === null) {
                return null;
            }

            return [
                'id' => $this->deliveryAgent->delivery_agent_id,
                'name' => $this->deliveryAgent->user?->name,
            ];
        }

        if (! $this->relationLoaded('shippingCompany') || $this->shippingCompany === null) {
            return null;
        }

        return [
            'id' => $this->shippingCompany->shipping_company_id,
            'name' => $this->shippingCompany->company_name ?: $this->shippingCompany->user?->name,
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
