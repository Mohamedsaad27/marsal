<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShippingCompany */
class ShippingCompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->shipping_company_id,
            'company_name' => $this->company_name,
            'commercial_reg' => $this->commercial_reg,
            'logo_url' => $this->logo_url,
            'commission' => $this->formatCommission(),
            'balance' => $this->balance,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function formatCommission(): array
    {
        $type = (int) $this->commission_type;

        return [
            'type' => [
                'code' => $type,
                'label' => __("users::commission_types.{$type}"),
            ],
            'value' => $this->commission_value,
        ];
    }
}
