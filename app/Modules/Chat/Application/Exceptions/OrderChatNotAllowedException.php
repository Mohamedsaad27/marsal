<?php

namespace App\Modules\Chat\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class OrderChatNotAllowedException extends BaseException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message);
    }

    protected function getDefaultMessage(): string
    {
        return __('chat::messages.order_chat_not_allowed');
    }

    protected function getDefaultStatusCode(): int
    {
        return 422;
    }
}
