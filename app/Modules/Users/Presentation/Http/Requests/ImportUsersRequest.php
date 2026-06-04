<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => __('users::messages.import_file_required'),
            'file.mimes' => __('users::messages.import_file_invalid_type'),
            'file.max' => __('users::messages.import_file_too_large'),
        ];
    }
}
