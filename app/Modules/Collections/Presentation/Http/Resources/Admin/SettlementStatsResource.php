<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_amount' => $this->resource['total_amount'],
            'pending_approval' => $this->resource['pending_approval'],
            'approved_unpaid' => $this->resource['approved_unpaid'],
            'paid_this_month' => $this->resource['paid_this_month'],
        ];
    }
}
