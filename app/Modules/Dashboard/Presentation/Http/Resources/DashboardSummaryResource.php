<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_orders' => $this->resource['total_orders'],
            'total_orders_change_percent' => $this->resource['total_orders_change_percent'],
            'in_delivery' => $this->resource['in_delivery'],
            'in_delivery_label' => $this->resource['in_delivery_label'],
            'delivered_this_week' => $this->resource['delivered_this_week'],
            'delivered_change_percent' => $this->resource['delivered_change_percent'],
            'net_balance_companies' => $this->resource['net_balance_companies'],
            'net_balance_change_percent' => $this->resource['net_balance_change_percent'],
        ];
    }
}
