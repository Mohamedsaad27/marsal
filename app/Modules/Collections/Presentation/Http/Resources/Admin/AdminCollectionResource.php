<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Admin;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Collection */
class AdminCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->collection_id,
            'order' => $this->when(
                $this->relationLoaded('order') && $this->order !== null,
                fn () => [
                    'id' => $this->order->order_id,
                    'internal_code' => $this->order->reference_code,
                ],
            ),
            'agent' => $this->when(
                $this->relationLoaded('deliveryAgent') && $this->deliveryAgent !== null,
                fn () => [
                    'id' => $this->deliveryAgent->delivery_agent_id,
                    'name' => $this->deliveryAgent->user?->name,
                ],
            ),
            'company' => $this->when(
                $this->relationLoaded('shippingCompany') && $this->shippingCompany !== null,
                fn () => [
                    'id' => $this->shippingCompany->shipping_company_id,
                    'name' => $this->shippingCompany->company_name ?: $this->shippingCompany->user?->name,
                ],
            ),
            'collection_type' => [
                'code' => $this->collection_type?->value,
                'label' => $this->collection_type?->labelAr(),
            ],
            'collected_amount' => $this->collected_amount,
            'agent_commission_amount' => $this->agent_commission_amount,
            'agent_net_due' => $this->agent_net_due,
            'system_commission_amount' => $this->system_commission_amount,
            'company_net_due' => $this->company_net_due,
            'agent_settlement_status' => $this->settlementStatus(SettlementTypeEnum::Agent),
            'company_settlement_status' => $this->settlementStatus(SettlementTypeEnum::Company),
            'cash_received_at' => $this->cash_received_at?->toISOString(),
            'cash_received_by' => $this->when(
                $this->relationLoaded('cashReceivedBy') && $this->cashReceivedBy !== null,
                fn () => [
                    'id' => $this->cashReceivedBy->user_id,
                    'name' => $this->cashReceivedBy->name,
                ],
            ),
            'collected_at' => $this->collected_at?->toISOString(),
        ];
    }

    private function settlementStatus(SettlementTypeEnum $type): ?array
    {
        $status = $this->settlementStatusFor($type);

        if (! $status instanceof SettlementStatusEnum) {
            return null;
        }

        return [
            'code' => $status->value,
            'label' => $status->labelAr(),
        ];
    }
}
