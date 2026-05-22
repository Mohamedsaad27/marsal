<?php

namespace App\Modules\Auth\Domain\Services;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return ['required', 'string', 'confirmed', self::rule()];
    }
}
