<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderDeletionBlockedException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }

    protected function getDefaultMessage(): string
    {
        return __('orders::messages.order_delete_not_pending');
    }

    protected function getDefaultStatusCode(): int
    {
        return 409;
    }
}
