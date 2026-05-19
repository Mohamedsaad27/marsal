<?php

namespace App\Modules\Users\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class UnauthorizedUserException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('users::messages.unauthorized');
    }

    public function getDefaultStatusCode(): int
    {
        return 401;
    }
}