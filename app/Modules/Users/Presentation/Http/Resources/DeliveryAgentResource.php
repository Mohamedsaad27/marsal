<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Domain\Enums\CommissionTypeEnum;
use App\Modules\Users\Domain\Enums\VehicleTypeEnum;
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
        $vehicleType = $this->vehicle_type instanceof VehicleTypeEnum
            ? $this->vehicle_type
            : VehicleTypeEnum::tryFrom((int) $this->vehicle_type);

        if ($vehicleType === null) {
            return null;
        }

        return [
            'type' => [
                'code' => $vehicleType->value,
                'label' => $vehicleType->labelAr(),
            ],
            'plate_number' => $this->vehicle_plate_number,
        ];
    }

    private function formatCommission(): array
    {
        $commissionType = $this->commission_type instanceof CommissionTypeEnum
            ? $this->commission_type
            : CommissionTypeEnum::tryFrom((int) $this->commission_type);

        return [
            'type' => [
                'code' => $commissionType?->value,
                'label' => $commissionType?->labelAr(),
            ],
            'value' => $this->commission_value,
        ];
    }
}
