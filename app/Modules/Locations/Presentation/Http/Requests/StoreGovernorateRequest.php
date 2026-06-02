<?php

namespace App\Modules\Locations\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreGovernorateRequest extends BaseFormRequest
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
            'name_ar' => ['required', 'string', 'max:191'],
            'name_en' => ['required', 'string', 'max:191'],
            'code' => [
                'nullable',
                'string',
                'max:191',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('governorates', 'code')->whereNull('deleted_at'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
