<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AgentOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof OrderStatusEnum
            ? $this->status
            : OrderStatusEnum::tryFrom((int) $this->status);

        return [
            'id' => $this->order_id,
            'order_id' => $this->order_id,
            'reference_code' => $this->reference_code,
            'internal_code' => $this->reference_code,
            'status' => [
                'id' => $status?->value,
                'label' => $status?->labelAr(),
                'color' => $status?->badgeColor(),
            ],
            'customer_name' => $this->customerInfo?->customer_name,
            'customer_phone' => $this->customerInfo?->customer_phone,
            'city' => $this->address?->city?->name_ar ?? $this->address?->address_line,
            'governorate' => $this->address?->governorate?->name_ar,
            'cod_amount' => $this->financials?->original_amount !== null
                ? (float) $this->financials->original_amount
                : null,
            'expected_delivery_date' => $this->schedule?->expected_delivery_date?->toDateString(),
        ];
    }
}
