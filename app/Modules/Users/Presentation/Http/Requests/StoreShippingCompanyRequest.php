<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use App\Modules\Users\Presentation\Http\Requests\Concerns\ValidatesUserAddress;

class StoreShippingCompanyRequest extends BaseFormRequest
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
            'profile.company_name' => ['required', 'string', 'max:200'],
            'profile.commercial_reg' => ['nullable', 'string', 'max:100'],
            ...$this->addressRules(),
        ];
    }
}
