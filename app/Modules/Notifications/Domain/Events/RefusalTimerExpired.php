<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired when the refusal timer expires with no resolution.
 * Recipients: shipping company user AND the delivery agent user.
 */
readonly class RefusalTimerExpired
{
    public function __construct(
        public string $companyUserId,
        public string $agentUserId,
        public string $orderCode,
        public string $orderId,
    ) {}
}
