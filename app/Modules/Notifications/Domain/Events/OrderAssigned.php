<?php

namespace App\Modules\Notifications\Domain\Events;

/**
 * Fired when an order is assigned to a delivery agent.
 * Recipient: delivery agent.
 */
readonly class OrderAssigned
{
    public function __construct(
        public string $agentUserId,
        public string $orderCode,
        public string $orderId,
    ) {}
}
