<?php

namespace App\Modules\Reports\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingCompaniesReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->shipping_company_id,
            'company_name' => $this->company_name ?: $this->user?->name,
            'contact_name' => $this->user?->name,
            'phone' => $this->user?->phone,
            'email' => $this->user?->email,
            'commercial_reg' => $this->commercial_reg,
            'is_active' => (bool) $this->is_active,
            'balance' => $this->balance,
            'metrics' => $this->report_metrics ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
