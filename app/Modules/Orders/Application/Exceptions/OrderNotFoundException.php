<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderNotFoundException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return 'الطلب غير موجود';
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
