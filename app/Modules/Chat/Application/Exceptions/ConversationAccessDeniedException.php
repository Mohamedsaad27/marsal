<?php

namespace App\Modules\Chat\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class ConversationAccessDeniedException extends BaseException
{
    protected function getDefaultMessage(): string
    {
        return __('chat::messages.access_denied');
    }

    protected function getDefaultStatusCode(): int
    {
        return 403;
    }
}
