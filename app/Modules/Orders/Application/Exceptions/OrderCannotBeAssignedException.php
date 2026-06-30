<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderCannotBeAssignedException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return 'لا يمكن إعادة تعيين طلب تم تسليمه أو تسليمه جزئياً أو تم تسليمه بتغيير في سعر';
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
