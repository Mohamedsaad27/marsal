<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['required', 'string', 'uuid', 'exists:delivery_agents,delivery_agent_id'],
        ];
    }
}
