<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired when an agent's phone number is updated.
 * Recipient: the delivery agent user (security alert).
 */
readonly class PhoneUpdated
{
    public function __construct(
        public string $agentUserId,
    ) {}
}
