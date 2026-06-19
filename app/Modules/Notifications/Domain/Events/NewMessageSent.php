<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired when a new chat message is sent.
 * Recipient: the other participant(s) in the conversation.
 */
readonly class NewMessageSent
{
    public function __construct(
        public string $recipientUserId,
        public string $senderName,
        public string $orderCode,
        public string $orderId,
        public string $conversationId,
    ) {}
}
