<?php

namespace App\Modules\Collections\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentCollectionsSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_unsettled' => (float) $this->resource['total_unsettled'],
            'unsettled_count' => (int) $this->resource['unsettled_count'],
            'breakdown' => [
                'cod' => (float) $this->resource['breakdown']['cod'],
                'shipping_fee' => (float) $this->resource['breakdown']['shipping_fee'],
                'partial' => (float) $this->resource['breakdown']['partial'],
            ],
            'last_settlement_date' => $this->resource['last_settlement_date'],
            'agent_balance' => (float) $this->resource['agent_balance'],
        ];
    }
}
