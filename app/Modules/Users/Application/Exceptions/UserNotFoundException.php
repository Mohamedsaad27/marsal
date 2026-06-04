<?php

namespace App\Modules\Users\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class UserNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('users::messages.user_not_found');
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
