<?php

namespace App\Modules\Auth\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OtpRateLimitException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }

    protected function getDefaultMessage(): string
    {
        return __('auth::messages.otp_rate_limited');
    }

    protected function getDefaultStatusCode(): int
    {
        return 429;
    }
}
