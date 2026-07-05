<?php

namespace App\Modules\Reports\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionsReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->collection_id,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order?->order_id,
                'reference_code' => $this->order?->reference_code,
            ]),
            'agent' => $this->whenLoaded('deliveryAgent', fn () => [
                'id' => $this->deliveryAgent?->delivery_agent_id,
                'name' => $this->deliveryAgent?->user?->name,
            ]),
            'company' => $this->whenLoaded('shippingCompany', fn () => [
                'id' => $this->shippingCompany?->shipping_company_id,
                'name' => $this->shippingCompany?->company_name ?: $this->shippingCompany?->user?->name,
            ]),
            'collection_type' => [
                'code' => $this->collection_type?->value,
                'label' => $this->collection_type?->labelAr(),
            ],
            'collected_amount' => $this->collected_amount,
            'commission_amount' => $this->commission_amount,
            'net_due' => $this->net_due,
            'cash_received_at' => $this->cash_received_at?->toISOString(),
            'settlement_id' => $this->settlement_id,
            'collected_at' => $this->collected_at?->toISOString(),
        ];
    }
}
