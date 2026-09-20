<?php

namespace App\Modules\Reports\Presentation\Http\Resources;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionsReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->collection_id,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order?->order_id,
                'reference_code' => $this->order?->reference_code,
            ]),
            'agent' => $this->whenLoaded('deliveryAgent', fn () => [
                'id' => $this->deliveryAgent?->delivery_agent_id,
                'name' => $this->deliveryAgent?->user?->name,
            ]),
            'company' => $this->whenLoaded('shippingCompany', fn () => [
                'id' => $this->shippingCompany?->shipping_company_id,
                'name' => $this->shippingCompany?->company_name ?: $this->shippingCompany?->user?->name,
            ]),
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
