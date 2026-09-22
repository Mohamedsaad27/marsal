<?php

namespace App\Modules\Returns\Presentation\Http\Resources\Admin;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderReturn */
class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $orderStatusId = $this->order_status_id
            ?? $this->order?->getRawOriginal('status');
        $orderStatus = $orderStatusId !== null
            ? OrderStatusEnum::tryFrom((int) $orderStatusId)
            : null;

        return [
            'id' => $this->return_id,
            'order_id' => $this->order_id,
            'return_status' => [
                'id' => $this->return_status?->value,
                'label' => $this->return_status?->labelAr(),
                'color' => $this->return_status?->badgeColor(),
            ],
            'returned_quantity' => $this->returned_quantity,
            'return_reason' => $this->return_reason,
            'notes' => $this->notes,
            'order' => $this->when(
                $this->relationLoaded('order') && $this->order !== null,
                fn () => [
                    'id' => $this->order->order_id,
                    'reference_code' => $this->order->reference_code,
                    'reference_no' => $this->order->reference_no,
                    'status' => [
                        'id' => (int) $orderStatusId,
                        'label' => $orderStatus?->labelAr() ?? 'حالة طلب قديمة',
                        'color' => $orderStatus?->badgeColor() ?? 'gray',
                    ],
                    'customer' => [
                        'name' => $this->order->customerInfo?->customer_name,
                        'phone' => $this->order->customerInfo?->customer_phone,
                        'phone_alt' => $this->order->customerInfo?->phone_alt,
                    ],
                    'address' => [
                        'governorate' => $this->order->address?->governorate?->name_ar,
                        'city' => $this->order->address?->city?->name_ar,
                        'address_line' => $this->order->address?->address_line,
                    ],
                ],
            ),
            'agent' => $this->when(
                $this->relationLoaded('deliveryAgent') && $this->deliveryAgent !== null,
                fn () => [
                    'id' => $this->deliveryAgent->delivery_agent_id,
                    'name' => $this->deliveryAgent->user?->name,
                ]
            ),
            'company' => $this->when(
                $this->relationLoaded('shippingCompany') && $this->shippingCompany !== null,
                fn () => [
                    'id' => $this->shippingCompany->shipping_company_id,
                    'name' => $this->shippingCompany->user?->name,
                ]
            ),
            'received_at' => $this->received_at?->toISOString(),
            'returned_to_company_at' => $this->returned_to_company_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
