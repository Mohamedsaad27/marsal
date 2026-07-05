<?php

namespace App\Modules\Reports\Presentation\Http\Resources;

use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementsReportResource extends JsonResource
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
            'entity' => $this->entity(),
            'collections_count' => (int) ($this->collections_count ?? 0),
            'total_collections' => $this->total_collections,
            'total_commissions' => $this->total_commissions,
            'net_amount' => $this->net_amount,
            'period_from' => $this->period_from?->toDateString(),
            'period_to' => $this->period_to?->toDateString(),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function entity(): ?array
    {
        if ($this->settlement_type === SettlementTypeEnum::Agent) {
            return $this->whenLoaded('deliveryAgent', fn () => $this->deliveryAgent === null ? null : [
                'id' => $this->deliveryAgent->delivery_agent_id,
                'name' => $this->deliveryAgent->user?->name,
            ]);
        }

        return $this->whenLoaded('shippingCompany', fn () => $this->shippingCompany === null ? null : [
            'id' => $this->shippingCompany->shipping_company_id,
            'name' => $this->shippingCompany->company_name ?: $this->shippingCompany->user?->name,
        ]);
    }
}
