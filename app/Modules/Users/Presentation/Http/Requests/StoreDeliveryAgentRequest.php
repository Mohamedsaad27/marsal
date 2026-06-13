<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use App\Modules\Users\Presentation\Http\Requests\Concerns\ValidatesUserAddress;

class StoreDeliveryAgentRequest extends BaseFormRequest
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
            'profile.national_id' => ['nullable', 'string', 'max:20', 'unique:delivery_agents,national_id'],
            'profile.vehicle_type' => ['nullable', 'integer', 'min:1', 'max:5'],
            'profile.vehicle_plate_number' => ['nullable', 'string', 'max:30'],
            'profile.supervisor_agent_id' => ['nullable', 'uuid', 'exists:delivery_agents,delivery_agent_id'],
            ...$this->addressRules(),
        ];
    }
}
