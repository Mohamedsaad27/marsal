<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentScheduleCalendarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'month' => $this->resource['month'],
            'total_postponed' => (int) $this->resource['total_postponed'],
            'dates' => $this->resource['dates'],
        ];
    }
}
