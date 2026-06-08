<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvgDeliveryTimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'avg_hours' => $this->resource['avg_hours'],
            'change_percent' => $this->resource['change_percent'],
            'change_direction' => $this->resource['change_direction'],
            'comparison_label' => $this->resource['comparison_label'],
        ];
    }
}
