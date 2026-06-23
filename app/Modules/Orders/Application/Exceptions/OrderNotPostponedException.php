<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderNotPostponedException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return 'الطلب ليس في حالة مؤجل';
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
