<?php

namespace App\Modules\Returns\Presentation\Http\Resources\Admin;

use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderReturn */
class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->return_id,
            'order_id'      => $this->order_id,
            'return_status' => [
                'id'    => $this->return_status?->value,
                'label' => $this->return_status?->labelAr(),
                'color' => $this->return_status?->badgeColor(),
            ],
            'returned_quantity' => $this->returned_quantity,
            'return_reason'     => $this->return_reason,
            'notes'             => $this->notes,
            'agent' => $this->when(
                $this->relationLoaded('deliveryAgent') && $this->deliveryAgent !== null,
                fn () => [
                    'id'   => $this->deliveryAgent->delivery_agent_id,
                    'name' => $this->deliveryAgent->user?->name,
                ]
            ),
            'company' => $this->when(
                $this->relationLoaded('shippingCompany') && $this->shippingCompany !== null,
                fn () => [
                    'id'   => $this->shippingCompany->shipping_company_id,
                    'name' => $this->shippingCompany->user?->name,
                ]
            ),
            'received_at'            => $this->received_at?->toISOString(),
            'returned_to_company_at' => $this->returned_to_company_at?->toISOString(),
            'created_at'             => $this->created_at?->toISOString(),
        ];
    }
}
