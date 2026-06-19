<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired when a delivery agent starts the refusal timer on an order.
 * Recipient: shipping company user.
 */
readonly class RefusalTimerStarted
{
    public function __construct(
        public string $companyUserId,
        public string $orderCode,
        public string $orderId,
        public string $agentName,
        public int    $timerMinutes,
    ) {}
}
