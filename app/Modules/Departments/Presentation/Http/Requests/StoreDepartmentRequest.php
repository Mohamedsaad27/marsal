<?php

namespace App\Modules\Departments\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends BaseFormRequest
{
    protected function translationNamespace(): string
    {
        return 'departments';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:150', Rule::unique('departments', 'name_ar')->whereNull('deleted_at')],
            'name_en' => ['required', 'string', 'max:150', Rule::unique('departments', 'name_en')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,user_id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
