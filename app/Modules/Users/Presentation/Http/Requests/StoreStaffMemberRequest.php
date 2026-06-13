<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use App\Modules\Users\Presentation\Http\Requests\Concerns\ValidatesUserAddress;

class StoreStaffMemberRequest extends BaseFormRequest
{
    use ValidatesUserAddress;
    protected function translationNamespace(): string
    {
        return 'users';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'max:100'],
            'profile' => ['nullable', 'array'],
            'profile.department_id' => ['nullable', 'uuid', 'exists:departments,department_id'],
            'profile.job_title' => ['nullable', 'string', 'max:150'],
            'profile.notes' => ['nullable', 'string'],
            ...$this->addressRules(),
        ];
    }
}
