<?php

namespace App\Modules\Locations\Presentation\Http\Resources;

use App\Modules\Locations\Infrastructure\Database\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin City */
class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'city_id' => $this->city_id,
            'governorate_id' => $this->governorate_id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'governorate' => new GovernorateResource($this->whenLoaded('governorate')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
