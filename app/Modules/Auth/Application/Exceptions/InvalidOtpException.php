<?php

namespace App\Modules\Auth\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class InvalidOtpException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('auth::messages.invalid_otp');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
