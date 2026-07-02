<?php

namespace App\Modules\Locations\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;

class BulkDeleteCitiesRequest extends BaseFormRequest
{
    protected function translationNamespace(): string
    {
        return 'locations';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'uuid', 'distinct', 'exists:cities,city_id'],
        ];
    }
}
