<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Users\Presentation\Http\Requests\Concerns\HasListUserFilters;
use Illuminate\Foundation\Http\FormRequest;

class ListStaffMembersRequest extends FormRequest
{
    use HasListUserFilters;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->listUserFilterRules(), [
            'department_id' => ['nullable', 'uuid', 'exists:departments,department_id'],
        ]);
    }
}
