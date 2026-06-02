<?php

namespace App\Modules\Locations\Presentation\Http\Resources;

use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Governorate */
class GovernorateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'governorate_id' => $this->governorate_id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'cities_count' => $this->when(isset($this->cities_count), $this->cities_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
