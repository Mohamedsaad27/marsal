<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;

class ForgotPasswordRequest extends BaseFormRequest
{
    protected function translationNamespace(): string
    {
        return 'auth';
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
