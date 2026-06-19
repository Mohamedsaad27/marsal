<?php

namespace App\Modules\Dashboard\Presentation\Http\Requests;

use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecentOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStatuses = array_column(OrderStatusEnum::cases(), 'value');

        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'nullable', 'integer', Rule::in($validStatuses)],
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'sort_by' => ['sometimes', 'string', Rule::in(['created_at', 'updated_at', 'reference_code', 'status'])],
            'sort_dir' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
