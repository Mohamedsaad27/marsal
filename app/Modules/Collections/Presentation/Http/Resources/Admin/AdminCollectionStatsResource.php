<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCollectionStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_collected' => $this->resource['total_collected'],
            'total_commissions' => $this->resource['total_commissions'],
            'net_due_to_companies' => $this->resource['net_due_to_companies'],
            'pending_cash_count' => $this->resource['pending_cash_count'],
        ];
    }
}
