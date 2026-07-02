<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteAdminOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'uuid', 'distinct', 'exists:orders,order_id'],
        ];
    }
}
