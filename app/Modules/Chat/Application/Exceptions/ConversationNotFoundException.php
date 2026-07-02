<?php

namespace App\Modules\Chat\Application\Exceptions;

use App\Modules\Core\Application\Exceptions\BaseException;

class ConversationNotFoundException extends BaseException
{
    public function __construct(?string $conversationId = null)
    {
        parent::__construct(
            $conversationId
                ? __('chat::messages.conversation_not_found', ['id' => $conversationId])
                : null,
        );
    }

    protected function getDefaultMessage(): string
    {
        return __('chat::messages.conversation_not_found', ['id' => '']);
    }

    protected function getDefaultStatusCode(): int
    {
        return 404;
    }
}
