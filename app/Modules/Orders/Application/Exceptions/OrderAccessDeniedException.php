<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderAccessDeniedException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return 'لا يمكنك الوصول إلى هذا الطلب';
    }

    protected function getDefaultStatusCode(): int
    {
        return 403;
    }
}
