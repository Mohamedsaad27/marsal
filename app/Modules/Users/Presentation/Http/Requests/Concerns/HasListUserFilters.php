<?php

namespace App\Modules\Users\Presentation\Http\Requests\Concerns;

trait HasListUserFilters
{
    /** @return array<string, mixed> */
    protected function listUserFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'integer', 'in:0,1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
