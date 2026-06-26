<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class ListCompanyOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'    => ['nullable', 'string', 'in:all,pending,in_delivery,delivered,returned'],
            'search'    => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
