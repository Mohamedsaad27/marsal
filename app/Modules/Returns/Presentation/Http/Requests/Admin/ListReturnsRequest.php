<?php

namespace App\Modules\Returns\Presentation\Http\Requests\Admin;

use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class ListReturnsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStatuses = implode(',', array_column(ReturnStatusEnum::cases(), 'value'));

        return [
            'status'     => ['nullable', 'integer', "in:{$validStatuses}"],
            'company_id' => ['nullable', 'string', 'uuid'],
            'agent_id'   => ['nullable', 'string', 'uuid'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
