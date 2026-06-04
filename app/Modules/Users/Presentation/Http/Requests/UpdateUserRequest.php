<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseFormRequest
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
        $userId = $this->route('userId');
        $deliveryAgentId = DeliveryAgent::query()->where('user_id', $userId)->value('delivery_agent_id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'user_id'),
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId, 'user_id'),
            ],
            'gender' => ['sometimes', 'nullable', 'string', 'max:20'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:500'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:100'],
            'profile' => ['sometimes', 'array'],
            'profile.company_name' => ['sometimes', 'string', 'max:200'],
            'profile.commercial_reg' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.logo_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'profile.department' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profile.job_title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'profile.notes' => ['sometimes', 'nullable', 'string'],
            'profile.national_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('delivery_agents', 'national_id')
                    ->ignore($deliveryAgentId, 'delivery_agent_id'),
            ],
            'profile.vehicle_type' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'profile.vehicle_plate_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'profile.supervisor_agent_id' => [
                'sometimes',
                'nullable',
                'uuid',
                'exists:delivery_agents,delivery_agent_id',
            ],
        ];
    }
}
