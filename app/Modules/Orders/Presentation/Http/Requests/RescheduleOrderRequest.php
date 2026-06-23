<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'postponed_date' => ['required', 'date', 'date_format:Y-m-d', 'after:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
