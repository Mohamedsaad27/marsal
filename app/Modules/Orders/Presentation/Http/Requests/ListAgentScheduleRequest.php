<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAgentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
        ];
    }
}
