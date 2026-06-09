<?php

namespace App\Modules\Departments\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class DepartmentNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('departments::messages.not_found');
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
