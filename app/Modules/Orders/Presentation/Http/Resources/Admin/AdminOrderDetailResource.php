<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Admin;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AdminOrderDetailResource extends JsonResource
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
                'id'               => $status?->value,
                'label'            => $status?->labelAr(),
                'color'            => $status?->badgeColor(),
                'is_terminal'      => $status?->isTerminal(),
                'requires_collection' => $status?->requiresCollection(),
            ],
            'customer' => [
                'name'      => $this->customerInfo?->customer_name,
                'phone'     => $this->customerInfo?->customer_phone,
                'phone_alt' => $this->customerInfo?->phone_alt,
            ],
            'address' => [
                'governorate_id' => $this->address?->governorate_id,
                'governorate'    => $this->address?->governorate?->name_ar,
                'city_id'        => $this->address?->city_id,
                'city'           => $this->address?->city?->name_ar,
                'address_line'   => $this->address?->address_line,
            ],
            'financials' => $this->when(
                $this->relationLoaded('financials') && $this->financials !== null,
                fn () => [
                    'original_amount'  => (float) $this->financials->original_amount,
                    'approved_amount'  => $this->financials->approved_amount !== null
                        ? (float) $this->financials->approved_amount
                        : null,
                    'collected_amount' => $this->financials->collected_amount !== null
                        ? (float) $this->financials->collected_amount
                        : null,
                    'shipping_fee'     => (float) $this->financials->shipping_fee,
                    'commission_amount' => $this->financials->commission_amount !== null
                        ? (float) $this->financials->commission_amount
                        : null,
                    'net_due_company'  => $this->financials->net_due_company !== null
                        ? (float) $this->financials->net_due_company
                        : null,
                    'is_settled'       => (bool) $this->financials->is_settled,
                ]
            ),
            'items' => $this->when(
                $this->relationLoaded('items') && $this->items !== null,
                fn () => [
                    'description'        => $this->items->item_description,
                    'total_quantity'     => $this->items->total_quantity,
                    'delivered_quantity' => $this->items->delivered_quantity,
                    'returned_quantity'  => $this->items->returned_quantity,
                ]
            ),
            'schedule' => $this->when(
                $this->relationLoaded('schedule') && $this->schedule !== null,
                fn () => [
                    'expected_delivery_date' => $this->schedule->expected_delivery_date?->toDateString(),
                    'postponed_date'         => $this->schedule->postponed_date?->toDateString(),
                    'schedule_notes'         => $this->schedule->schedule_notes,
                ]
            ),
            'company' => $this->when(
                $this->relationLoaded('shippingCompany') && $this->shippingCompany !== null,
                fn () => [
                    'id'   => $this->shippingCompany->shipping_company_id,
                    'name' => $this->shippingCompany->user?->name,
                    'phone' => $this->shippingCompany->user?->phone,
                ]
            ),
            'agent' => $this->when(
                $this->delivery_agent_id !== null && $this->relationLoaded('deliveryAgent'),
                fn () => $this->deliveryAgent !== null ? [
                    'id'    => $this->deliveryAgent->delivery_agent_id,
                    'name'  => $this->deliveryAgent->user?->name,
                    'phone' => $this->deliveryAgent->user?->phone,
                ] : null
            ),
            'status_history' => $this->when(
                $this->relationLoaded('statusHistory'),
                fn () => $this->statusHistory->map(fn ($h) => [
                    'from_status_id' => $h->from_status_id,
                    'from_label'     => $h->from_status_id
                        ? OrderStatusEnum::tryFrom((int) $h->from_status_id)?->labelAr()
                        : null,
                    'to_status_id'   => $h->to_status_id,
                    'to_label'       => OrderStatusEnum::tryFrom((int) $h->to_status_id)?->labelAr(),
                    'changed_by'     => $h->changedByUser?->name,
                    'notes'          => $h->notes,
                    'changed_at'     => $h->created_at?->toISOString(),
                ])->values()
            ),
            'proofs' => $this->when(
                $this->relationLoaded('proofs'),
                fn () => $this->proofs->map(fn ($p) => [
                    'proof_id'  => $p->order_proof_id,
                    'file_url'  => $p->file_url,
                    'file_type' => $p->file_type?->value,
                    'uploaded_at' => $p->created_at?->toISOString(),
                ])->values()
            ),
            'assigned_at'  => $this->assigned_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
