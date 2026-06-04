<?php

namespace App\Modules\Locations\Presentation\Http\Resources;

use App\Modules\Locations\Infrastructure\Database\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Address */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'address_id' => $this->address_id,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city?->city_id,
                'name_ar' => $this->city?->name_ar,
                'name_en' => $this->city?->name_en,
            ]),
            'address_line' => $this->address_line,
            'landmark' => $this->landmark,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'floor_number' => $this->floor_number,
            'apartment_number' => $this->apartment_number,
            'is_default' => $this->is_default,
        ];
    }
}
