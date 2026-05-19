<?php

namespace App\Modules\Auth\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class RoleNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('users::messages.role_not_found');
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
