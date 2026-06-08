<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success_rate' => $this->resource['success_rate'],
            'failed_count' => $this->resource['failed_count'],
            'pending_count' => $this->resource['pending_count'],
            'success_count' => $this->resource['success_count'],
        ];
    }
}
