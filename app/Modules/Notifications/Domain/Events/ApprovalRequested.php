<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired when an agent requests a price/fee change approval.
 * Recipient: shipping company user.
 */
readonly class ApprovalRequested
{
    public function __construct(
        public string $companyUserId,
        public string $orderCode,
        public string $orderId,
        public string $agentName,
        public string $newAmount,
    ) {}
}
