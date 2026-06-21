<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentOrderStatusUpdatedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['order_id'],
            'order_id' => $this->resource['order_id'],
            'new_status' => [
                'id' => $this->resource['new_status']['id'],
                'label' => $this->resource['new_status']['label'],
            ],
            'collection_created' => (bool) $this->resource['collection_created'],
        ];
    }
}
