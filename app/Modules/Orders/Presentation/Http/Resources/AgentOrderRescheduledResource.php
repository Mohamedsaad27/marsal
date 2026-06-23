<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentOrderRescheduledResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->resource['order_id'],
            'postponed_date' => $this->resource['postponed_date'],
            'status' => [
                'id' => $this->resource['status']['id'],
                'label' => $this->resource['status']['label'],
            ],
        ];
    }
}
