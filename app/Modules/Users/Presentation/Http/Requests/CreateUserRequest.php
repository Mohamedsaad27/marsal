<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Users\Application\DTOs\CreateUserDTO;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends BaseFormRequest
{
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
            'account_type' => ['required', 'string', Rule::in(['super_admin', 'shipping_company', 'delivery_agent'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:100'],
            'profile' => ['nullable', 'array'],
            'profile.company_name' => ['required_if:account_type,shipping_company', 'string', 'max:200'],
            'profile.commercial_reg' => ['nullable', 'string', 'max:100'],
            'profile.national_id' => ['nullable', 'string', 'max:20', 'unique:delivery_agents,national_id'],
            'profile.vehicle_type' => ['nullable', 'integer', 'min:1', 'max:5'],
            'profile.vehicle_plate_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function toDTO(): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            phone: $this->string('phone')->toString(),
            password: $this->string('password')->toString(),
            accountType: AccountTypeEnum::fromCode($this->string('account_type')->toString()),
            roles: $this->input('roles', []),
            profile: $this->input('profile', []),
        );
    }
}
