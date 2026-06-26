<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastSettlement = $this->resource['last_settlement'];

        return [
            'company_id'     => $this->resource['company_id'],
            'company_name'   => $this->resource['company_name'],
            'phone'          => $this->resource['phone'],
            'commercial_reg' => $this->resource['commercial_reg'],
            'logo_url'       => $this->resource['logo_url'],
            'balance'        => (float) $this->resource['balance'],
            'stats'          => [
                'total_orders'          => (int) $this->resource['stats']['total_orders'],
                'delivery_rate_percent' => (int) $this->resource['stats']['delivery_rate_percent'],
            ],
            'last_settlement' => $lastSettlement !== null ? [
                'reference'  => $lastSettlement['reference'],
                'net_amount' => (float) $lastSettlement['net_amount'],
                'paid_at'    => $lastSettlement['paid_at'],
            ] : null,
        ];
    }
}
