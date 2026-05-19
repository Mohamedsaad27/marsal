<?php

namespace App\Modules\Roles\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;

class StoreRoleRequest extends BaseFormRequest
{
    protected function translationNamespace(): string
    {
        return 'roles';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
        ];
    }
}
