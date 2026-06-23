<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Admin;

use App\Modules\Orders\Domain\Enums\ApprovalStatusEnum;
use App\Modules\Orders\Domain\Enums\ApprovalTypeEnum;
use App\Modules\Orders\Infrastructure\Database\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApprovalRequest */
class ApprovalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->approval_request_id,
            'order_id'         => $this->order_id,
            'order_code'       => $this->order?->reference_code,
            'approval_type'    => [
                'id'    => $this->approval_type?->value,
                'label' => $this->resolveTypeLabel($this->approval_type),
            ],
            'approval_status'  => [
                'id'    => $this->approval_status?->value,
                'label' => $this->resolveStatusLabel($this->approval_status),
            ],
            'original_amount'  => (float) $this->original_amount,
            'requested_amount' => (float) $this->requested_amount,
            'reason'           => $this->reason,
            'review_notes'     => $this->review_notes,
            'expires_at'       => $this->expires_at?->toISOString(),
            'expires_in_minutes' => $this->when(
                $this->approval_status === ApprovalStatusEnum::Pending && $this->expires_at !== null,
                fn () => max(0, (int) now()->diffInMinutes($this->expires_at, false))
            ),
            'requested_by'     => $this->when(
                $this->relationLoaded('requestedByUser') && $this->requestedByUser !== null,
                fn () => [
                    'id'   => $this->requestedByUser->user_id,
                    'name' => $this->requestedByUser->name,
                ]
            ),
            'reviewed_by' => $this->when(
                $this->relationLoaded('reviewedByUser') && $this->reviewedByUser !== null,
                fn () => [
                    'id'   => $this->reviewedByUser->user_id,
                    'name' => $this->reviewedByUser->name,
                ]
            ),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'order' => $this->when(
                $this->relationLoaded('order') && $this->order !== null,
                fn () => [
                    'order_id'       => $this->order->order_id,
                    'reference_code' => $this->order->reference_code,
                    'company_name'   => $this->order->shippingCompany?->user?->name,
                    'agent_name'     => $this->order->deliveryAgent?->user?->name,
                    'customer_name'  => $this->order->customerInfo?->customer_name,
                    'governorate'    => $this->order->address?->governorate?->name_ar,
                    'city'           => $this->order->address?->city?->name_ar,
                ]
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function resolveTypeLabel(?ApprovalTypeEnum $type): ?string
    {
        if ($type === null) {
            return null;
        }

        return match ($type) {
            ApprovalTypeEnum::PriceChange   => 'تعديل سعر',
            ApprovalTypeEnum::ShippingFee   => 'رسوم شحن',
            ApprovalTypeEnum::PartialAmount => 'تحصيل جزئي',
        };
    }

    private function resolveStatusLabel(?ApprovalStatusEnum $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return match ($status) {
            ApprovalStatusEnum::Pending  => 'بانتظار الرد',
            ApprovalStatusEnum::Approved => 'تمت الموافقة',
            ApprovalStatusEnum::Rejected => 'مرفوضة',
            ApprovalStatusEnum::Expired  => 'منتهية',
        };
    }
}
