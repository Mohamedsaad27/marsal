<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Services\OrderStatusTransitionService;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Orders\Infrastructure\Database\Models\OrderProof;
use App\Modules\Orders\Infrastructure\Database\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AgentOrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof OrderStatusEnum
            ? $this->status
            : OrderStatusEnum::from((int) $this->status);

        $financials = $this->financials;
        $codAmount = $financials?->original_amount !== null ? (float) $financials->original_amount : 0.0;
        $shippingFee = $financials?->shipping_fee !== null ? (float) $financials->shipping_fee : 0.0;

        return [
            'order_id' => $this->order_id,
            'reference_code' => $this->reference_code,
            'company_name' => $this->display_company_name ?: $this->shippingCompany?->company_name,
            'status' => [
                'id' => $status->value,
                'label' => $status->labelAr(),
            ],
            'customer' => [
                'name' => $this->customerInfo?->customer_name,
                'phone_1' => $this->customerInfo?->customer_phone,
                'phone_2' => $this->customerInfo?->phone_alt,
            ],
            'address' => [
                'governorate' => $this->address?->governorate?->name_ar,
                'city' => $this->address?->city?->name_ar,
                'address_line' => $this->address?->address_line,
            ],
            'financials' => [
                'cod_amount' => $codAmount,
                'shipping_fee' => $shippingFee,
                'collected_amount' => $financials?->collected_amount !== null
                    ? (float) $financials->collected_amount
                    : null,
                'total_due' => $codAmount,
            ],
            'items' => [
                'quantity' => $this->items?->total_quantity,
                'description' => $this->items?->item_description ?: $this->notes,
                'delivered_quantity' => $this->items?->delivered_quantity,
            ],
            'schedule' => [
                'expected_delivery_date' => $this->schedule?->expected_delivery_date?->toDateString(),
                'postponed_date' => $this->schedule?->postponed_date?->toDateString(),
            ],
            'approvals' => [
                'requires_approval' => (bool) ($this->approvals?->requires_approval ?? false),
                'approval_granted' => $this->approvals?->approval_granted,
            ],
            'status_history' => $this->whenLoaded(
                'statusHistory',
                fn () => $this->statusHistory->map(
                    static function (OrderStatusHistory $entry) {
                        $toStatus = OrderStatusEnum::tryFrom((int) $entry->to_status_id);

                        return [
                            'status_id' => (int) $entry->to_status_id,
                            'status_label' => $toStatus?->labelAr(),
                            'changed_at' => $entry->created_at?->toISOString(),
                            'changed_by' => $entry->changedByUser?->name ?? 'النظام',
                            'notes' => $entry->notes,
                        ];
                    },
                )->values()->all(),
            ),
            'proof_photos' => $this->whenLoaded(
                'proofs',
                fn () => $this->proofs->map(
                    static function (OrderProof $proof) {
                        return [
                            'id' => $proof->order_proof_id,
                            'file_url' => $proof->file_url,
                            'file_type' => $proof->file_type?->value,
                        ];
                    },
                )->values()->all(),
            ),
            'available_actions' => app(OrderStatusTransitionService::class)->availableActions($status),
        ];
    }
}
