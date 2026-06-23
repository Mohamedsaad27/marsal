<?php

namespace App\Modules\Returns\Presentation\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total'             => $this->resource['total'] ?? 0,
            'pending'           => $this->resource['pending'] ?? 0,
            'received_by_admin' => $this->resource['received_by_admin'] ?? 0,
            'sent_to_company'   => $this->resource['sent_to_company'] ?? 0,
        ];
    }
}
