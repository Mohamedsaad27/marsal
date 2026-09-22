<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'order_ids.*' => ['required', 'string', 'uuid', 'distinct', 'exists:orders,order_id'],
            'agent_id' => ['required', 'string', 'uuid', 'exists:delivery_agents,delivery_agent_id'],
        ];
    }
}
