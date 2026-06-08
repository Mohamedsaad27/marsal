<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionsBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_pending' => $this->resource['total_pending'],
            'currency' => $this->resource['currency'],
            'company_count' => $this->resource['company_count'],
        ];
    }
}
