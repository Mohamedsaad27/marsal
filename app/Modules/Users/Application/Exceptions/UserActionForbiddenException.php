<?php

namespace App\Modules\Users\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class UserActionForbiddenException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }

    protected function getDefaultMessage(): string
    {
        return __('users::messages.action_forbidden');
    }

    protected function getDefaultStatusCode(): int
    {
        return 403;
    }
}
