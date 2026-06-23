<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Domain\Enums\CommissionTypeEnum;
use App\Modules\Users\Domain\Enums\VehicleTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\AgentZone;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource['user'];

        /** @var DeliveryAgent $agent */
        $agent = $this->resource['agent'];

        $vehicleType = $agent->vehicle_type instanceof VehicleTypeEnum
            ? $agent->vehicle_type
            : VehicleTypeEnum::tryFrom((int) $agent->vehicle_type);

        $commissionType = $agent->commission_type instanceof CommissionTypeEnum
            ? $agent->commission_type
            : CommissionTypeEnum::tryFrom((int) $agent->commission_type);

        return [
            'user_id' => $user->user_id,
            'name' => $user->name,
            'phone' => $user->phone,
            'agent' => [
                'agent_id' => $agent->delivery_agent_id,
                'vehicle_type' => [
                    'id' => $vehicleType?->value,
                    'label' => $vehicleType?->labelAr(),
                ],
                'vehicle_plate' => $agent->vehicle_plate_number,
                'national_id' => $agent->national_id,
                'commission_type' => [
                    'id' => $commissionType?->value,
                    'label' => $commissionType?->labelAr(),
                ],
                'commission_value' => $agent->commission_value !== null
                    ? (float) $agent->commission_value
                    : null,
                'balance' => (float) $agent->balance,
                'is_active' => (bool) $user->is_active,
            ],
            'stats' => [
                'total_delivered' => (int) $this->resource['stats']['total_delivered'],
                'average_rating' => $this->resource['stats']['average_rating'],
                'active_since' => $this->resource['stats']['active_since'],
            ],
            'zones' => AgentProfileZoneResource::collection(
                $agent->relationLoaded('zones') ? $agent->zones : collect(),
            ),
        ];
    }

    
}
