<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use App\Modules\Auth\Domain\Services\PasswordPolicy;
use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;

class ResetPasswordRequest extends BaseFormRequest
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
        $otpLength = (int) config('auth_module.otp_length', 6);

        return [
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'digits:' . $otpLength],
            'password' => PasswordPolicy::rules(),
        ];
    }
}
