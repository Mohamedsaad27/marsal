<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;

class BulkDeleteUsersRequest extends BaseFormRequest
{
    protected function translationNamespace(): string
    {
        return 'users';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'uuid', 'distinct', 'exists:users,user_id'],
        ];
    }
}
