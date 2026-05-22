<?php

namespace App\Modules\Auth\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OtpResendCooldownException extends BaseException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct(__('auth::messages.otp_resend_cooldown', ['seconds' => $retryAfterSeconds]));
    }

    protected function getDefaultMessage(): string
    {
        return __('auth::messages.otp_resend_cooldown', ['seconds' => 60]);
    }

    protected function getDefaultStatusCode(): int
    {
        return 429;
    }
}
