<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stats'         => [
                'total_orders'          => (int) $this->resource['stats']['total_orders'],
                'in_delivery_count'     => (int) $this->resource['stats']['in_delivery_count'],
                'collected_today'       => (float) $this->resource['stats']['collected_today'],
                'delivery_rate_percent' => (int) $this->resource['stats']['delivery_rate_percent'],
            ],
            'recent_orders' => CompanyOrderListResource::collection($this->resource['recent_orders']),
        ];
    }
}
