<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AgentUpcomingOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->order_id,
            'reference_code' => $this->reference_code,
            'status_id' => $this->status->value,
            'status_label' => $this->status->labelAr(),
            'company_name' =>  $this->shippingCompany?->company_name,
            'customer_name' => $this->customerInfo?->customer_name,
            'customer_phone' => $this->customerInfo?->customer_phone,
            'address' => $this->address?->city?->name_ar ?? $this->address?->address_line,
            'governorate' => $this->address?->governorate?->name_ar,
            'cod_amount' => $this->financials?->original_amount !== null
                ? (float) $this->financials->original_amount
                : null,
        ];
    }
}
