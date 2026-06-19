<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'يرجى رفع ملف Excel',
            'file.mimes'    => 'الملف يجب أن يكون xlsx أو xls',
            'file.max'      => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
        ];
    }
}
