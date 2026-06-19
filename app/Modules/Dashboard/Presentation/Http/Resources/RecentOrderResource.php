<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use App\Modules\Dashboard\Infrastructure\Database\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class RecentOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $governorate = $this->address?->governorate?->name_ar;
        $city = $this->address?->city?->name_ar;
        $region = ($governorate && $city)
            ? $governorate.' / '.$city
            : ($governorate ?? $city);

        $status = $this->status instanceof OrderStatusEnum
            ? $this->status
            : OrderStatusEnum::tryFrom((int) $this->status);

        return [
            'order_id'       => $this->order_id,
            'reference_code' => $this->reference_code,
            'reference_no'   => $this->reference_no,
            'customer_name' => $this->customerInfo?->customer_name,
            'customer_phone' => $this->customerInfo?->customer_phone,
            'region' => $region,
            'company_name' => $this->shippingCompany?->company_name,
            'agent_name' => $this->deliveryAgent?->user?->name,
            'agent_avatar_url' => $this->deliveryAgent?->user?->avatar,
            'original_amount' => $this->financials?->original_amount !== null
                ? (float) $this->financials->original_amount
                : null,
            'collected_amount' => $this->financials?->collected_amount !== null
                ? (float) $this->financials->collected_amount
                : null,
            'status' => $status?->value ?? (int) $this->status,
            'status_label_ar' => $status?->labelAr(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
