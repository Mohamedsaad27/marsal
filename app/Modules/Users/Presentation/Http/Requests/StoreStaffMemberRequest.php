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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['nullable', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:100'],
            'profile' => ['nullable', 'array'],
            'profile.department' => ['nullable', 'string', 'max:100'],
            'profile.job_title' => ['nullable', 'string', 'max:150'],
            'profile.notes' => ['nullable', 'string'],
            ...$this->addressRules(),
        ];
    }
}
