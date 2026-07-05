<?php

namespace App\Modules\Reports\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdersReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof OrderStatusEnum
            ? $this->status
            : OrderStatusEnum::tryFrom((int) $this->status);

        return [
            'id' => $this->order_id,
            'reference_code' => $this->reference_code,
            'reference_no' => $this->reference_no,
            'status' => [
                'code' => $status?->value,
                'label' => $status?->labelAr(),
                'color' => $status?->badgeColor(),
            ],
            'customer' => [
                'name' => $this->customerInfo?->customer_name,
                'phone' => $this->customerInfo?->customer_phone,
            ],
            'company' => $this->whenLoaded('shippingCompany', fn () => [
                'id' => $this->shippingCompany?->shipping_company_id,
                'name' => $this->shippingCompany?->company_name ?: $this->shippingCompany?->user?->name,
            ]),
            'agent' => $this->whenLoaded('deliveryAgent', fn () => $this->deliveryAgent === null ? null : [
                'id' => $this->deliveryAgent->delivery_agent_id,
                'name' => $this->deliveryAgent->user?->name,
            ]),
            'address' => [
                'governorate' => $this->address?->governorate?->name_ar,
                'city' => $this->address?->city?->name_ar,
                'address_line' => $this->address?->address_line,
            ],
            'financials' => [
                'original_amount' => $this->financials?->original_amount,
                'collected_amount' => $this->financials?->collected_amount,
                'commission_amount' => $this->financials?->commission_amount,
                'net_due_company' => $this->financials?->net_due_company,
                'is_settled' => (bool) ($this->financials?->is_settled ?? false),
            ],
            'assigned_at' => $this->assigned_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
