<?php

namespace App\Modules\Departments\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class DepartmentHasMembersException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('departments::messages.has_members');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
