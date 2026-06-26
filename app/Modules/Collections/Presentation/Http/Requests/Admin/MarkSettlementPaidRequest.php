<?php

namespace App\Modules\Collections\Presentation\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarkSettlementPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
