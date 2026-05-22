<?php

namespace App\Modules\Auth\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OtpExpiredException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('auth::messages.otp_expired');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
