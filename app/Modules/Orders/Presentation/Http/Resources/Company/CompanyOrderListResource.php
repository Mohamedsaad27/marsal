<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Company;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class CompanyOrderListResource extends JsonResource
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
            'status'         => [
                'id'    => $status?->value,
                'label' => $status?->labelAr(),
                'color' => $status?->badgeColor(),
            ],
            'customer'       => [
                'name'  => $this->customerInfo?->customer_name,
                'phone' => $this->customerInfo?->customer_phone,
            ],
            'address'        => [
                'governorate' => $this->address?->governorate?->name_ar,
                'city'        => $this->address?->city?->name_ar,
                'address_line' => $this->address?->address_line,
            ],
            'amount'         => $this->financials?->original_amount !== null
                ? (float) $this->financials->original_amount
                : null,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
