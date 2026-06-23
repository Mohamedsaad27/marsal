<?php

namespace App\Modules\Orders\Presentation\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'       => ['required', 'string', 'in:approve,reject'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
