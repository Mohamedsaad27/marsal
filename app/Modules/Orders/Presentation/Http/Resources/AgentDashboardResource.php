<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'agent' => [
                'name' => $this->resource['agent']['name'],
                'fcm_token_registered' => (bool) $this->resource['agent']['fcm_token_registered'],
            ],
            'today' => [
                'orders_count' => (int) $this->resource['today']['orders_count'],
                'collected_amount' => (float) $this->resource['today']['collected_amount'],
                'delivered_count' => (int) $this->resource['today']['delivered_count'],
                'pending_count' => (int) $this->resource['today']['pending_count'],
            ],
            'performance' => [
                'delivery_rate_percent' => (int) $this->resource['performance']['delivery_rate_percent'],
                'week_label' => $this->resource['performance']['week_label'],
            ],
            'upcoming_orders' => AgentUpcomingOrderResource::collection(
                $this->resource['upcoming_orders'],
            ),
        ];
    }
}
