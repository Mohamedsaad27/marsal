<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Admin;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStatuses = array_map(
            static fn (OrderStatusEnum $s) => (string) $s->value,
            OrderStatusEnum::cases()
        );
        $validStatuses[] = 'all';
        $validStatuses[] = 'pending';
        $validStatuses[] = 'in_delivery';
        $validStatuses[] = 'delivered';
        $validStatuses[] = 'postponed_refused';
        $validStatuses[] = 'returned';

        return [
            'status'         => ['nullable', 'string', Rule::in($validStatuses)],
            'company_id'     => ['nullable', 'string', 'uuid'],
            'agent_id'       => ['nullable', 'string', 'uuid'],
            'governorate_id' => ['nullable', 'string', 'uuid'],
            'date_from'      => ['nullable', 'date_format:Y-m-d'],
            'date_to'        => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'search'         => ['nullable', 'string', 'max:100'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
