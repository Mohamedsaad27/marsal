<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentDefinitionsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_statuses' => $this->resource['order_statuses'],
            'collection_types' => $this->resource['collection_types'],
            'proof_file_types' => $this->resource['proof_file_types'],
            'order_list_filters' => $this->resource['order_list_filters'],
            'collection_settled_filters' => $this->resource['collection_settled_filters'],
            'refusal_resolutions' => $this->resource['refusal_resolutions'],
        ];
    }
}
