<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderCannotBeAssignedException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return 'لا يمكن إعادة تعيين الطلب في هذه الحالة';
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
