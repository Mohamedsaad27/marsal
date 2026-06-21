<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class InvalidOrderStatusTransitionException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }

    protected function getDefaultMessage(): string
    {
        return 'لا يمكن تغيير حالة الطلب إلى الحالة المطلوبة';
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
