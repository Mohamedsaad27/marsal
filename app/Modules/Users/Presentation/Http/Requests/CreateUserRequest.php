<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Users\Application\DTOs\CreateUserDTO;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Domain\Enums\CommissionTypeEnum;
use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use App\Modules\Users\Presentation\Http\Requests\Concerns\ValidatesUserAddress;
use Illuminate\Validation\Rule;

class CreateUserRequest extends BaseFormRequest
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
            'type' => ['required', 'string', Rule::in(AccountTypeEnum::codes())],
            'role' => ['required', 'string', 'max:100'],
            'profile' => ['nullable', 'array'],
            'profile.company_name' => ['required_if:type,shipping_company', 'string', 'max:200'],
            'profile.commercial_reg' => ['nullable', 'string', 'max:100'],
            'profile.department_id' => ['nullable', 'uuid', 'exists:departments,department_id'],
            'profile.job_title' => ['nullable', 'string', 'max:150'],
            'profile.notes' => ['nullable', 'string'],
            'profile.national_id' => ['nullable', 'string', 'max:20', 'unique:delivery_agents,national_id'],
            'profile.vehicle_type' => ['nullable', 'integer', 'min:1', 'max:5'],
            'profile.vehicle_plate_number' => ['nullable', 'string', 'max:30'],
            'profile.supervisor_agent_id' => ['nullable', 'uuid', 'exists:delivery_agents,delivery_agent_id'],
            'profile.commission_type' => ['nullable', 'integer', Rule::in([CommissionTypeEnum::Fixed->value])],
            'profile.commission_value' => ['nullable', 'numeric', 'min:0'],
            ...$this->addressRules(required: $this->string('type')->toString() !== 'super_admin'),
        ];
    }

    public function toDTO(): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $this->string('name')->toString(),
            email: $this->filled('email') ? $this->string('email')->toString() : null,
            phone: $this->string('phone')->toString(),
            password: $this->string('password')->toString(),
            accountType: AccountTypeEnum::fromCode($this->string('type')->toString()),
            roles: [$this->string('role')->toString()],
            profile: $this->input('profile', []),
            address: $this->input('address', []),
        );
    }
}
