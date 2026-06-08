<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopAgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->resource['rank'],
            'delivery_agent_id' => $this->resource['delivery_agent_id'],
            'name' => $this->resource['name'],
            'city' => $this->resource['city'],
            'avatar_url' => $this->resource['avatar_url'],
            'shipments_today' => $this->resource['shipments_today'],
        ];
    }
}
