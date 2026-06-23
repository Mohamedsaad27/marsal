<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Admin;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AdminOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof OrderStatusEnum
            ? $this->status
            : OrderStatusEnum::tryFrom((int) $this->status);

        return [
            'order_id'       => $this->order_id,
            'reference_code' => $this->reference_code,
            'reference_no'   => $this->reference_no,
            'display_company_name' => $this->display_company_name,
            'notes'          => $this->notes,
            'status'         => [
                'id'    => $status?->value,
                'label' => $status?->labelAr(),
                'color' => $status?->badgeColor(),
            ],
            'customer' => [
                'name'      => $this->customerInfo?->customer_name,
                'phone'     => $this->customerInfo?->customer_phone,
                'phone_alt' => $this->customerInfo?->phone_alt,
            ],
            'address' => [
                'governorate' => $this->address?->governorate?->name_ar,
                'city'        => $this->address?->city?->name_ar,
                'address_line' => $this->address?->address_line,
            ],
            'company' => $this->when(
                $this->relationLoaded('shippingCompany') && $this->shippingCompany !== null,
                fn () => [
                    'id'   => $this->shippingCompany->shipping_company_id,
                    'name' => $this->shippingCompany->user?->name,
                ]
            ),
            'agent' => $this->when(
                $this->delivery_agent_id !== null && $this->relationLoaded('deliveryAgent'),
                fn () => $this->deliveryAgent !== null ? [
                    'id'   => $this->deliveryAgent->delivery_agent_id,
                    'name' => $this->deliveryAgent->user?->name,
                ] : null
            ),
            'amount'      => $this->financials?->original_amount !== null
                ? (float) $this->financials->original_amount
                : null,
            'assigned_at' => $this->assigned_at?->toISOString(),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
