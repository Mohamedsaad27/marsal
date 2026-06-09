<?php

namespace App\Modules\Departments\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends BaseFormRequest
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
        $departmentId = $this->route('departmentId');

        return [
            'name_ar' => ['sometimes', 'string', 'max:150', Rule::unique('departments', 'name_ar')->ignore($departmentId, 'department_id')->whereNull('deleted_at')],
            'name_en' => ['sometimes', 'string', 'max:150', Rule::unique('departments', 'name_en')->ignore($departmentId, 'department_id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,user_id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
