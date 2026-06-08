<?php

namespace App\Modules\Users\Presentation\Http\Requests\Concerns;

trait ValidatesUserAddress
{
    protected function addressRules(bool $required = true): array
    {
        $presence = $required ? ['required'] : ['nullable'];

        return [
            'address' => array_merge($presence, ['array']),
            'address.city_id' => ['nullable', 'uuid', 'exists:cities,city_id'],
            'address.address_line' => array_merge($required ? ['required'] : ['nullable'], ['string', 'max:500']),
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.building_number' => ['nullable', 'string', 'max:50'],
            'address.floor_number' => ['nullable', 'string', 'max:20'],
            'address.apartment_number' => ['nullable', 'string', 'max:20'],
            'address.is_default' => ['nullable', 'boolean'],
        ];
    }
}
