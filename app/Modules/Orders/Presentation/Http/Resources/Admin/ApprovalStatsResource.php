<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'awaiting' => $this->resource['awaiting'] ?? 0,
            'urgent'   => $this->resource['urgent'] ?? 0,
            'approved' => $this->resource['approved'] ?? 0,
            'rejected' => $this->resource['rejected'] ?? 0,
            'expired'  => $this->resource['expired'] ?? 0,
        ];
    }
}
