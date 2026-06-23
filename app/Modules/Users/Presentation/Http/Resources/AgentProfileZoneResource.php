<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Infrastructure\Database\Models\AgentZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AgentZone */
class AgentProfileZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->agent_zone_id,
            'zone_id' => $this->agent_zone_id,
            'city' => $this->whenLoaded(
                'city',
                fn () => $this->city?->name_ar,
            ),
            'governorate' => $this->whenLoaded(
                'governorate',
                fn () => $this->governorate?->name_ar,
            ),
            'is_primary' => (bool) $this->is_primary,
        ];
    }
}
