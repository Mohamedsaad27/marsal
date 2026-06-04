<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryAgent */
class DeliveryAgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->delivery_agent_id,
            'supervisor_agent_id' => $this->supervisor_agent_id,
            'is_supervisor' => $this->isSupervisor(),
            'national_id' => $this->national_id,
            'vehicle' => $this->formatVehicle(),
            'commission' => $this->formatCommission(),
            'balance' => $this->balance,
            'is_available' => (bool) $this->is_available,
            'supervisor' => $this->when(
                $this->supervisor_agent_id !== null && $this->relationLoaded('supervisor'),
                fn () => [
                    'id' => $this->supervisor?->delivery_agent_id,
                    'name' => $this->supervisor?->user?->name,
                ],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function formatVehicle(): ?array
    {
        if ($this->vehicle_type === null) {
            return null;
        }

        $type = (int) $this->vehicle_type;

        return [
            'type' => [
                'code' => $type,
                'label' => __("users::vehicle_types.{$type}"),
            ],
            'plate_number' => $this->vehicle_plate_number,
        ];
    }

    private function formatCommission(): array
    {
        $type = (int) $this->commission_type;

        return [
            'type' => [
                'code' => $type,
                'label' => __("users::commission_types.{$type}"),
            ],
            'value' => $this->commission_value,
        ];
    }
}
