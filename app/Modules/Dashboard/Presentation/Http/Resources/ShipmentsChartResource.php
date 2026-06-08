<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentsChartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'labels' => $this->resource['labels'],
            'delivered' => $this->resource['delivered'],
            'pending' => $this->resource['pending'],
        ];
    }
}
