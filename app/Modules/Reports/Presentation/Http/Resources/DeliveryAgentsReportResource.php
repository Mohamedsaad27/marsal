<?php

namespace App\Modules\Reports\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryAgentsReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->delivery_agent_id,
            'name' => $this->user?->name,
            'phone' => $this->user?->phone,
            'email' => $this->user?->email,
            'national_id' => $this->national_id,
            'vehicle_plate_number' => $this->vehicle_plate_number,
            'is_available' => (bool) $this->is_available,
            'balance' => $this->balance,
            'supervisor' => $this->whenLoaded('supervisor', fn () => $this->supervisor === null ? null : [
                'id' => $this->supervisor->delivery_agent_id,
                'name' => $this->supervisor->user?->name,
            ]),
            'metrics' => $this->report_metrics ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
