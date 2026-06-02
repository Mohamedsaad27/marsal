<?php

namespace App\Modules\Locations\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ParsesLocationListFilters
{
    /**
     * @return array<string, mixed>
     */
    protected function locationListFilters(Request $request, ?string $governorateId = null): array
    {
        return array_filter([
            'governorate_id' => $governorateId ?? ($request->string('governorate_id')->toString() ?: null),
            'search' => $request->string('search')->toString() ?: null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ], fn ($value) => $value !== null);
    }
}
