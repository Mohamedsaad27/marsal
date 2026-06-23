<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetScheduleCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
        ];
    }
}
