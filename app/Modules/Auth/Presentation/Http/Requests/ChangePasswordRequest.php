<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use App\Modules\Auth\Domain\Services\PasswordPolicy;
use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;

class ChangePasswordRequest extends BaseFormRequest
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
            'current_password' => ['required', 'string'],
            'password' => PasswordPolicy::rules(),
        ];
    }
}
