<?php

namespace App\Modules\Auth\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class InvalidCurrentPasswordException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('auth::messages.invalid_current_password');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
