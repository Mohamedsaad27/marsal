<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Users\Presentation\Http\Requests\Concerns\HasListUserFilters;
use Illuminate\Foundation\Http\FormRequest;

class ListDeliveryAgentsRequest extends FormRequest
{
    use HasListUserFilters;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->listUserFilterRules(), [
            'commission_type' => ['nullable', 'integer', 'in:1,2'],
        ]);
    }
}
