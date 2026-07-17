<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use App\Modules\Auth\Application\DTOs\LoginDTO;
use App\Modules\Core\Presentation\Http\Requests\BaseFormRequest;

class LoginRequest extends BaseFormRequest
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
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toDTO(): LoginDTO
    {
        return new LoginDTO(
            identifier: $this->string('identifier')->toString(),
            password: $this->string('password')->toString(),
            fcmToken: $this->filled('fcm_token') ? $this->string('fcm_token')->toString() : null,
        );
    }
}
