<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Users\Presentation\Http\Requests\Concerns\HasListUserFilters;
use Illuminate\Foundation\Http\FormRequest;

class ListShippingCompaniesRequest extends FormRequest
{
    use HasListUserFilters;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->listUserFilterRules(), [
            'city_id' => ['nullable', 'uuid', 'exists:cities,city_id'],
        ]);
    }
}
