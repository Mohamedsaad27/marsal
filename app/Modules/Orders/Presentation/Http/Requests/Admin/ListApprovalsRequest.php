<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Admin;

use App\Modules\Orders\Domain\Enums\ApprovalStatusEnum;
use App\Modules\Orders\Domain\Enums\ApprovalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class ListApprovalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStatuses = implode(',', array_column(ApprovalStatusEnum::cases(), 'value'));
        $validTypes    = implode(',', array_column(ApprovalTypeEnum::cases(), 'value'));

        return [
            'status'   => ['nullable', 'integer', "in:{$validStatuses}"],
            'type'     => ['nullable', 'integer', "in:{$validTypes}"],
            'agent_id' => ['nullable', 'string', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
