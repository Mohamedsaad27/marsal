<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired by the PostponedSchedules cron when a postponed delivery date arrives.
 * Recipient: the delivery agent user.
 */
readonly class PostponedReminderDue
{
    public function __construct(
        public string $agentUserId,
        public string $orderCode,
        public string $orderId,
        public string $scheduledDateAr,
    ) {}
}
