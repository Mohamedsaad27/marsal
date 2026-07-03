<?php

namespace App\Modules\Orders\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderAgentRequiredException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('orders::messages.order_agent_required');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
