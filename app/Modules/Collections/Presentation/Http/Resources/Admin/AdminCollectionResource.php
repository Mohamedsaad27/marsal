<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Admin;

use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Collection */
class AdminCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->collection_id,
            'order' => $this->when(
                $this->relationLoaded('order') && $this->order !== null,
                fn () => [
                    'id' => $this->order->order_id,
                    'internal_code' => $this->order->reference_code,
                ],
            ),
            'agent' => $this->when(
                $this->relationLoaded('deliveryAgent') && $this->deliveryAgent !== null,
                fn () => [
                    'id' => $this->deliveryAgent->delivery_agent_id,
                    'name' => $this->deliveryAgent->user?->name,
                ],
            ),
            'company' => $this->when(
                $this->relationLoaded('shippingCompany') && $this->shippingCompany !== null,
                fn () => [
                    'id' => $this->shippingCompany->shipping_company_id,
                    'name' => $this->shippingCompany->company_name ?: $this->shippingCompany->user?->name,
                ],
            ),
            'collection_type' => [
                'code' => $this->collection_type?->value,
                'label' => $this->collection_type?->labelAr(),
            ],
            'collected_amount' => $this->collected_amount,
            'commission_amount' => $this->commission_amount,
            'net_due' => $this->net_due,
            'cash_received_at' => $this->cash_received_at?->toISOString(),
            'cash_received_by' => $this->when(
                $this->relationLoaded('cashReceivedBy') && $this->cashReceivedBy !== null,
                fn () => [
                    'id' => $this->cashReceivedBy->user_id,
                    'name' => $this->cashReceivedBy->name,
                ],
            ),
            'settlement_id' => $this->settlement_id,
            'collected_at' => $this->collected_at?->toISOString(),
        ];
    }
}
