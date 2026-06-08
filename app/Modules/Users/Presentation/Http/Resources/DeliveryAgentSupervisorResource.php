<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryAgent */
class DeliveryAgentSupervisorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->delivery_agent_id,
            'name' => $this->user?->name,
            'phone' => $this->user?->phone,
            'is_active' => (bool) $this->user?->is_active,
            'is_available' => (bool) $this->is_available,
        ];
    }
}
