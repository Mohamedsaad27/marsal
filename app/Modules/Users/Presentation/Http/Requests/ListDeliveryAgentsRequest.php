<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Users\Domain\Enums\CommissionTypeEnum;
use App\Modules\Users\Presentation\Http\Requests\Concerns\HasListUserFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'commission_type' => ['nullable', 'integer', Rule::in([CommissionTypeEnum::Fixed->value])],
        ]);
    }
}
