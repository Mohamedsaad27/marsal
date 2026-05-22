<?php

namespace App\Modules\Auth\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class PasswordReuseException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }

    protected function getDefaultMessage(): string
    {
        return __('auth::messages.password_reuse');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
