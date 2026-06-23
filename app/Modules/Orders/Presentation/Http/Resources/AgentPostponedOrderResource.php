<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AgentPostponedOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->order_id,
            'order_id' => $this->order_id,
            'internal_code' => $this->reference_code,
            'customer_name' => $this->customerInfo?->customer_name,
            'city' => $this->address?->city?->name_ar ?? $this->address?->address_line,
            'cod_amount' => $this->financials?->original_amount !== null
                ? (float) $this->financials->original_amount
                : null,
            'postponed_date' => $this->schedule?->postponed_date?->toDateString(),
            'postpone_notes' => $this->schedule?->schedule_notes,
        ];
    }
}
